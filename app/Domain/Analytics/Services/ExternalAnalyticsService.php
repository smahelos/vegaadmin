<?php

namespace App\Domain\Analytics\Services;

/**
 * Example external analytics service demonstrating circuit breaker usage.
 * 
 * This service could be used for:
 * - Fetching market analytics from external providers
 * - Enriching analytics data with external sources
 * - Synchronizing with business intelligence platforms
 * 
 * Uses circuit breaker pattern to protect against external service failures.
 */
class ExternalAnalyticsService extends BaseAnalyticsExternalService
{
    /**
     * Service identifier for circuit breaker cache keys
     */
    private const SERVICE_KEY = 'external_analytics';

    /**
     * Fetch market data from external analytics provider.
     * 
     * This is an example method that demonstrates how to use the circuit breaker
     * for external API calls in the Analytics domain.
     *
     * @param string $sector Market sector to analyze
     * @return array|null Market data or null if unavailable
     */
    public function fetchMarketData(string $sector): ?array
    {
        return $this->executeWithCircuitBreaker(
            self::SERVICE_KEY . '_market',
            function () use ($sector) {
                // Example external API call
                $response = $this->httpClient->get("https://api.example.com/market/{$sector}");
                
                if (is_array($response) && isset($response['success']) && $response['success']) {
                    return $response['data'] ?? null;
                }
                
                if (is_array($response) && isset($response['market_data'])) {
                    return $response['market_data'];
                }
                
                return null;
            },
            function () use ($sector) {
                // Fallback data when external service is unavailable
                return [
                    'sector' => $sector,
                    'status' => 'fallback',
                    'trend' => 'stable',
                    'confidence' => 'low',
                    'last_updated' => null,
                ];
            }
        );
    }

    /**
     * Fetch industry benchmarks from external provider.
     *
     * @param string $industry Industry code
     * @return array|null Benchmark data or null if unavailable
     */
    public function fetchIndustryBenchmarks(string $industry): ?array
    {
        return $this->executeWithCircuitBreaker(
            self::SERVICE_KEY . '_benchmarks',
            function () use ($industry) {
                $response = $this->httpClient->get("https://api.example.com/benchmarks/{$industry}");
                
                if (is_array($response) && isset($response['benchmarks'])) {
                    return $response['benchmarks'];
                }
                
                return null;
            },
            function () use ($industry) {
                // Fallback benchmarks
                return [
                    'industry' => $industry,
                    'revenue_growth' => 0.05, // 5% default
                    'profit_margin' => 0.15,  // 15% default
                    'status' => 'fallback',
                ];
            }
        );
    }

    /**
     * Validate if external analytics service is healthy.
     * 
     * This can be used for health checks and monitoring.
     *
     * @return bool True if service is responding
     */
    public function isServiceHealthy(): bool
    {
        $result = $this->executeWithCircuitBreaker(
            self::SERVICE_KEY . '_health',
            function () {
                $response = $this->httpClient->get('https://api.example.com/health');
                return is_array($response) && ($response['status'] ?? null) === 'ok';
            },
            function () {
                return false; // Service is down
            }
        );

        return (bool)$result;
    }
}
