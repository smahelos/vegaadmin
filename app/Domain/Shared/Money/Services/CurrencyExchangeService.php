<?php

namespace App\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\Shared\Http\Contracts\HttpClientInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;
use App\Domain\Shared\Config\Contracts\Config;

/**
 * Currency exchange service.
 * Retrieves FX rates from external API with caching and provides conversion utilities.
 */
class CurrencyExchangeService implements CurrencyExchangeServiceInterface
{
    /**
     * External API endpoint for latest exchange rates.
     * @var string
     */
    private string $apiUrl = 'https://open.er-api.com/v6/latest';
    /**
     * Clock callable returning current unix timestamp (injectable for testing).
     * @var callable():int
     */
    private $clock;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheServiceInterface $cacheService,
        private readonly LogInterface $logger,
        private readonly Config $config,
        ?callable $clock = null
    )
    {
        $this->clock = $clock ?? static fn(): int => time();
    }

    /**
     * Cache Time-To-Live in seconds (1 hour).
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get exchange rate from one currency to another.
     * Returns 1.0 when currencies are identical, null when unavailable.
     *
     * @param string $fromCurrency ISO 4217 source currency code
     * @param string $toCurrency ISO 4217 target currency code
     * @return float|null Exchange rate or null
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        if (!$this->isValidIso($fromCurrency) || !$this->isValidIso($toCurrency)) {
            return null;
        }

        $rates = $this->getExchangeRates();
        if (isset($rates[$fromCurrency], $rates[$toCurrency])) {
            return $rates[$toCurrency] / $rates[$fromCurrency];
        }
        return null;
    }

    /**
     * Get all exchange rates for a provided base currency code.
     * Falls back to USD when an invalid base code is supplied.
     *
     * @param string $baseCurrency ISO 4217 code (default USD)
     * @return array<string,float> Associative array of currency => rate
     */
    public function getExchangeRates(string $baseCurrency = 'USD'): array
    {
        $baseCurrency = strtoupper($baseCurrency);
        if (!$this->isValidIso($baseCurrency)) {
            $baseCurrency = 'USD';
        }
        $cacheKey = 'exchange_rates_' . $baseCurrency;
        $cached = $this->cacheService->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }
        $apiRates = $this->fetchApiRates($baseCurrency);
        if (is_array($apiRates)) {
            $this->cacheService->put($cacheKey, $apiRates, self::CACHE_TTL);
            return $apiRates;
        }
        // Do NOT cache fallback so repeated calls can drive circuit breaker transitions.
        return $this->getFallbackRates();
    }

    /**
     * Convert a monetary amount between currencies using current rates.
     * Returns null when conversion is not possible.
     *
     * @param float $amount Amount to convert
     * @param string $fromCurrency Source ISO 4217 code
     * @param string $toCurrency Target ISO 4217 code
     * @return float|null Converted amount rounded to 2 decimals or null
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        return $rate === null ? null : round($amount * $rate, 2);
    }

    /**
     * Fetch raw rates from external API.
     *
     * @param string $baseCurrency ISO 4217 base code
     * @return array<string,float>|null Rates or null on error
     */
    private function fetchApiRates(string $baseCurrency): ?array
    {
        return $this->attemptFetchWithBreaker($baseCurrency);
    }

    /**
     * Provide fallback static rates when API unavailable.
     *
     * @return array<string,float> Fallback currency rates
     */
    private function getFallbackRates(): array
    {
        return [
            'USD' => 1.0,
            'EUR' => 0.85,
            'CZK' => 21.5,
            'GBP' => 0.73,
            'PLN' => 3.8,
            'HUF' => 300.0,
            'CHF' => 0.92,
        ];
    }

    /**
     * Validate ISO 4217 currency code format (3 uppercase letters).
     *
     * @param string $code Currency code
     * @return bool True if valid format
     */
    private function isValidIso(string $code): bool
    {
        return (bool)preg_match('/^[A-Z]{3}$/', $code);
    }

    /* ===================== Circuit Breaker & Retry ===================== */

    private function attemptFetchWithBreaker(string $baseCurrency): ?array
    {
        $failThreshold = (int)$this->config->get('exchange.fail_threshold', 3);
        $openTimeout = (int)$this->config->get('exchange.open_timeout_seconds', 60);

        $stateKey = 'fx_cb_state';
        $failKey = 'fx_cb_fail_count';
        $openedAtKey = 'fx_cb_opened_at';

        $state = $this->cacheService->get($stateKey);

        if ($state === 'open') {
            $now = ($this->clock)();
            $openedAt = (int)$this->cacheService->get($openedAtKey);
            if (($now - $openedAt) < $openTimeout) {
                // Short-circuit: do not hit external API
                return null; // Caller will fall back
            }
            // Transition to half-open (allow single probe)
            $state = 'half_open';
            $this->cacheService->put($stateKey, $state, $openTimeout);
        }

        $rates = $this->attemptFetchWithRetry($baseCurrency);
        if ($rates !== null) {
            $this->recordSuccess($stateKey, $failKey, $openedAtKey);
            return $rates;
        }

        // All attempts failed, record failure and log error for visibility
        $currentState = is_string($state) ? $state : 'closed';
        $this->recordFailure($stateKey, $failKey, $openedAtKey, $failThreshold, $openTimeout, $currentState);
        $this->logger->log(
            'error', 
            'Error fetching currency exchange rates after retries for base ' . $baseCurrency
        );
        return null;
    }

    private function attemptFetchWithRetry(string $baseCurrency): ?array
    {
        $attempts = (int)$this->config->get('exchange.max_retry_attempts', 3);
        $baseDelay = (int)$this->config->get('exchange.retry_base_delay_ms', 100);
        $jitterPct = (int)$this->config->get('exchange.retry_jitter_percent', 20);
        $skipSleep = (bool)$this->config->get('exchange.skip_retry_sleep', false); // Use configuration flag to control whether retry sleep should be skipped (e.g., during tests)

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                // Build URL with query param, since HttpClientService::get() treats the second argument as headers
                $url = $this->apiUrl . '?base=' . urlencode($baseCurrency);
                $response = $this->httpClient->get($url);

                // Accept both wrapped and raw response shapes from HttpClientInterface implementations
                // Wrapped shape: ['success' => bool, 'status' => int, 'data' => ['rates' => [...]]]
                // Raw shape:     ['rates' => [...]]
                if (is_array($response)) {
                    if (array_key_exists('success', $response)) {
                        // Wrapped response
                        if (!empty($response['success'])) {
                            $data = $response['data'] ?? null;
                            if (is_array($data) && isset($data['rates']) && is_array($data['rates'])) {
                                return $data['rates'];
                            }
                        } else {
                            $status = $response['status'] ?? null;
                            if (is_int($status) && $status >= 400 && $status < 500 && $status !== 429) {
                                // Do not retry non-rate-limiting client errors.
                                return null;
                            }
                        }
                    } elseif (isset($response['rates']) && is_array($response['rates'])) {
                        // Raw response with rates
                        return $response['rates'];
                    }
                }
            } catch (\Throwable $e) {
                    // Avoid noisy warnings in tests by guarding behind config flag
                    if ((bool)$this->config->get('exchange.log_attempt_warnings', false)) {
                        $this->logger->log('warning', 'FX fetch attempt failed: ' . ($e->getMessage()));
                    }
            }

            if ($i < $attempts && !$skipSleep) {
                $delay = $this->computeRetryDelay($i, $baseDelay, $jitterPct);
                usleep($delay * 1000); // convert ms to microseconds
            }
        }
        return null;
    }

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

    private function recordSuccess(string $stateKey, string $failKey, string $openedAtKey): void
    {
        $this->cacheService->put($failKey, 0, 300);
        if ($this->cacheService->get($stateKey) !== 'closed') {
            $this->cacheService->put($stateKey, 'closed', 300);
            $this->cacheService->forget($openedAtKey);
            // $this->logger->log('info', 'FX circuit reset to CLOSED.');
        }
    }

    private function recordFailure(string $stateKey, string $failKey, string $openedAtKey, int $failThreshold, int $openTimeout, string $currentState): void
    {
        // Increment failure count reliably (CacheService::increment returns bool in our adapter)
            // Compute fail count manually because increment() returns boolean in our cache adapter
            $previous = (int)($this->cacheService->get($failKey) ?? 0);
            $failCount = $previous + 1;
            $this->cacheService->put($failKey, $failCount, 300);

        if ($currentState === 'half_open') {
            // Immediate re-open on failure in half-open probe
            $this->cacheService->put($stateKey, 'open', $openTimeout);
            $this->cacheService->put($openedAtKey, ($this->clock)(), $openTimeout);
            $this->logger->log('warning', 'FX circuit re-opened after failed HALF_OPEN probe.');
            return;
        }

        if ($failCount >= $failThreshold && $this->cacheService->get($stateKey) !== 'open') {
            $this->cacheService->put($stateKey, 'open', $openTimeout);
            $this->cacheService->put($openedAtKey, ($this->clock)(), $openTimeout);
            $this->logger->log('warning', 'FX circuit transitioned to OPEN after '.$failCount.' consecutive failures.');
        }
    }
}
