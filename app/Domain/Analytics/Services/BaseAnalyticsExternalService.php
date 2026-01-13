<?php

namespace App\Domain\Analytics\Services;

use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Config\Contracts\Config;

/**
 * Base service for Analytics domain with circuit breaker pattern.
 * 
 * Provides reusable circuit breaker functionality for any future external API calls
 * in the Analytics domain (e.g., external analytics providers, data enrichment APIs).
 * 
 * Follows the same pattern as CurrencyExchangeService.
 */
abstract class BaseAnalyticsExternalService
{
    /**
     * Clock callable returning current unix timestamp (injectable for testing).
     * @var callable():int
     */
    private $clock;

    /**
     * Circuit breaker cache key prefix for this service
     */
    protected const CB_PREFIX = 'analytics_cb_';

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly CacheServiceInterface $cacheService,
        protected readonly LogInterface $logger,
        protected readonly Config $config,
        ?callable $clock = null
    ) {
        $this->clock = $clock ?? static fn(): int => time();
    }

    /**
     * Execute external API call with circuit breaker protection.
     *
     * @param string $serviceKey Unique key for this service (used in cache keys)
     * @param callable $fetchCallback Callback that performs the actual API call
     * @param callable|null $fallbackCallback Optional fallback when circuit is open
     * @return mixed Result from fetchCallback or fallbackCallback
     */
    protected function executeWithCircuitBreaker(
        string $serviceKey,
        callable $fetchCallback,
        ?callable $fallbackCallback = null
    ): mixed {
        $failThreshold = (int)$this->config->get('analytics.circuit_breaker.fail_threshold', 3);
        $openTimeout = (int)$this->config->get('analytics.circuit_breaker.open_timeout_seconds', 60);

        $stateKey = self::CB_PREFIX . $serviceKey . '_state';
        $failKey = self::CB_PREFIX . $serviceKey . '_fail_count';
        $openedAtKey = self::CB_PREFIX . $serviceKey . '_opened_at';

        $state = $this->cacheService->get($stateKey);

        if ($state === 'open') {
            $now = ($this->clock)();
            $openedAt = (int)$this->cacheService->get($openedAtKey);
            if (($now - $openedAt) < $openTimeout) {
                // Short-circuit: do not hit external API
                return $fallbackCallback ? $fallbackCallback() : null;
            }
            // Transition to half-open (allow single probe)
            $state = 'half_open';
            $this->cacheService->put($stateKey, $state, $openTimeout);
        }

        $result = $this->attemptFetchWithRetry($serviceKey, $fetchCallback);
        
        if ($result !== null) {
            $this->recordSuccess($stateKey, $failKey, $openedAtKey);
            return $result;
        }

        // All attempts failed, record failure and log error
        $currentState = is_string($state) ? $state : 'closed';
        $this->recordFailure($stateKey, $failKey, $openedAtKey, $failThreshold, $openTimeout, $currentState, $serviceKey);
        
        return $fallbackCallback ? $fallbackCallback() : null;
    }

    /**
     * Attempt fetch with retry logic.
     */
    private function attemptFetchWithRetry(string $serviceKey, callable $fetchCallback): mixed
    {
        $attempts = (int)$this->config->get('analytics.circuit_breaker.max_retry_attempts', 3);
        $baseDelay = (int)$this->config->get('analytics.circuit_breaker.retry_base_delay_ms', 100);
        $jitterPct = (int)$this->config->get('analytics.circuit_breaker.retry_jitter_percent', 20);
        $skipSleep = (bool)$this->config->get('analytics.circuit_breaker.skip_retry_sleep', false);

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $result = $fetchCallback();
                if ($result !== null) {
                    return $result;
                }
            } catch (\Throwable $e) {
                if ((bool)$this->config->get('analytics.circuit_breaker.log_attempt_warnings', false)) {
                    $this->logger->log('warning', 'Analytics external service attempt failed', [
                        'service_key' => $serviceKey,
                        'attempt' => $i,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($i < $attempts && !$skipSleep) {
                $delay = $this->computeRetryDelay($i, $baseDelay, $jitterPct);
                usleep($delay * 1000); // convert ms to microseconds
            }
        }
        
        return null;
    }

    /**
     * Compute retry delay with exponential backoff and jitter.
     */
    private function computeRetryDelay(int $attempt, int $baseDelayMs, int $jitterPercent): int
    {
        // Exponential backoff: base * 2^(attempt-1)
        $raw = $baseDelayMs * (2 ** ($attempt - 1));
        if ($jitterPercent > 0) {
            $jitter = (int)($raw * ($jitterPercent / 100));
            $delta = random_int(-$jitter, $jitter);
            $raw += $delta;
        }
        return max(1, $raw);
    }

    /**
     * Record successful external call.
     */
    private function recordSuccess(string $stateKey, string $failKey, string $openedAtKey): void
    {
        $this->cacheService->put($failKey, 0, 300);
        if ($this->cacheService->get($stateKey) !== 'closed') {
            $this->cacheService->put($stateKey, 'closed', 300);
            $this->cacheService->forget($openedAtKey);
        }
    }

    /**
     * Record failed external call and handle circuit state transitions.
     */
    private function recordFailure(
        string $stateKey,
        string $failKey,
        string $openedAtKey,
        int $failThreshold,
        int $openTimeout,
        string $currentState,
        string $serviceKey
    ): void {
        // Increment failure count
        $previous = (int)($this->cacheService->get($failKey) ?? 0);
        $failCount = $previous + 1;
        $this->cacheService->put($failKey, $failCount, 300);

        if ($currentState === 'half_open') {
            // Immediate re-open on failure in half-open probe
            $this->cacheService->put($stateKey, 'open', $openTimeout);
            $this->cacheService->put($openedAtKey, ($this->clock)(), $openTimeout);
            $this->logger->log('warning', 'Analytics circuit re-opened after failed HALF_OPEN probe', [
                'service_key' => $serviceKey,
                'fail_count' => $failCount,
            ]);
            return;
        }

        if ($failCount >= $failThreshold && $this->cacheService->get($stateKey) !== 'open') {
            $this->cacheService->put($stateKey, 'open', $openTimeout);
            $this->cacheService->put($openedAtKey, ($this->clock)(), $openTimeout);
            $this->logger->log('warning', 'Analytics circuit transitioned to OPEN', [
                'service_key' => $serviceKey,
                'fail_count' => $failCount,
                'threshold' => $failThreshold,
            ]);
        }
    }
}
