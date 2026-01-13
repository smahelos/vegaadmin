<?php

namespace Tests\Feature\Domain\Analytics\Services;

use App\Domain\Analytics\Services\ExternalAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExternalAnalyticsServiceCircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private ExternalAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExternalAnalyticsService::class);
        
        // Clear any existing circuit breaker state
        Cache::flush();
    }

    #[Test]
    public function circuit_breaker_opens_after_threshold_failures(): void
    {
        config()->set('analytics.circuit_breaker.fail_threshold', 2);
        config()->set('analytics.circuit_breaker.open_timeout_seconds', 60);

        Http::fakeSequence()
            ->pushStatus(500)
            ->pushStatus(500)
            ->pushStatus(200, ['market_data' => ['sector' => 'tech', 'trend' => 'up']]);

        // First failure
        $result1 = $this->service->fetchMarketData('tech');
        $this->assertIsArray($result1);
        $this->assertEquals('fallback', $result1['status']);

        // Second failure - should open circuit
        $result2 = $this->service->fetchMarketData('tech');
        $this->assertIsArray($result2);
        $this->assertEquals('fallback', $result2['status']);

        // Check circuit is open
        $this->assertEquals('open', Cache::get('analytics_cb_external_analytics_market_state'));

        // Third call should short-circuit (not use the successful HTTP response in queue)
        $result3 = $this->service->fetchMarketData('tech');
        $this->assertIsArray($result3);
        $this->assertEquals('fallback', $result3['status']);
    }

    #[Test]
    public function circuit_breaker_handles_successful_calls(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([
                'market_data' => ['sector' => 'finance', 'trend' => 'stable']
            ], 200)
        ]);

        $result = $this->service->fetchMarketData('finance');
        
        $this->assertIsArray($result);
        $this->assertEquals('finance', $result['sector']);
        $this->assertEquals('stable', $result['trend']);
        
        // Circuit should remain closed (but cache may contain 'closed' state)
        $circuitState = Cache::get('analytics_cb_external_analytics_market_state');
        $this->assertTrue($circuitState === null || $circuitState === 'closed');
    }

    #[Test]
    public function circuit_breaker_handles_different_service_keys(): void
    {
        config()->set('analytics.circuit_breaker.fail_threshold', 1);

        Http::fakeSequence()
            ->pushStatus(500) // For market data
            ->pushStatus(500); // For benchmarks - it will also fail and use fallback

        // Fail market data call
        $marketResult = $this->service->fetchMarketData('tech');
        $this->assertEquals('fallback', $marketResult['status']);
        $this->assertEquals('open', Cache::get('analytics_cb_external_analytics_market_state'));

        // Benchmarks should also fail and use fallback (different circuit)
        $benchmarkResult = $this->service->fetchIndustryBenchmarks('tech');
        $this->assertEquals('tech', $benchmarkResult['industry']);
        $this->assertEquals(0.05, $benchmarkResult['revenue_growth']); // Fallback value is 0.05
        $this->assertEquals('fallback', $benchmarkResult['status']);
        
        // Benchmarks circuit should also be open after failure
        $this->assertEquals('open', Cache::get('analytics_cb_external_analytics_benchmarks_state'));
    }

    #[Test]
    public function health_check_works_correctly(): void
    {
        Http::fake([
            'api.example.com/health' => Http::response(['status' => 'ok'], 200)
        ]);

        $result = $this->service->isServiceHealthy();
        
        $this->assertTrue($result);
    }

    #[Test]
    public function health_check_returns_false_when_service_down(): void
    {
        Http::fake([
            'api.example.com/health' => Http::response([], 500)
        ]);

        $result = $this->service->isServiceHealthy();
        
        $this->assertFalse($result);
    }

    #[Test]
    public function fallback_data_structure_is_correct(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([], 500)
        ]);

        $marketResult = $this->service->fetchMarketData('tech');
        
        $this->assertIsArray($marketResult);
        $this->assertArrayHasKey('sector', $marketResult);
        $this->assertArrayHasKey('status', $marketResult);
        $this->assertArrayHasKey('trend', $marketResult);
        $this->assertArrayHasKey('confidence', $marketResult);
        $this->assertEquals('tech', $marketResult['sector']);
        $this->assertEquals('fallback', $marketResult['status']);

        $benchmarkResult = $this->service->fetchIndustryBenchmarks('tech');
        
        $this->assertIsArray($benchmarkResult);
        $this->assertArrayHasKey('industry', $benchmarkResult);
        $this->assertArrayHasKey('revenue_growth', $benchmarkResult);
        $this->assertArrayHasKey('profit_margin', $benchmarkResult);
        $this->assertArrayHasKey('status', $benchmarkResult);
        $this->assertEquals('tech', $benchmarkResult['industry']);
        $this->assertEquals('fallback', $benchmarkResult['status']);
    }
}
