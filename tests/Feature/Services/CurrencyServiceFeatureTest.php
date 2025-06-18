<?php

namespace Tests\Feature\Services;

use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyService();
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CurrencyService::class, $this->service);
    }

    #[Test]
    public function get_all_currencies_returns_api_data(): void
    {
        // Mock successful API response
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5,
                    'GBP' => 0.73,
                    'PLN' => 3.8
                ]
            ], 200)
        ]);

        $result = $this->service->getAllCurrencies();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('CZK', $result);
        $this->assertArrayHasKey('GBP', $result);
        $this->assertArrayHasKey('PLN', $result);
        
        // Check that values are the same as keys (currency code format)
        $this->assertEquals('USD', $result['USD']);
        $this->assertEquals('EUR', $result['EUR']);
        $this->assertEquals('CZK', $result['CZK']);
    }

    #[Test]
    public function get_all_currencies_returns_sorted_data(): void
    {
        // Mock API response with unsorted data
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'ZAR' => 15.0,
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'AUD' => 1.35
                ]
            ], 200)
        ]);

        $result = $this->service->getAllCurrencies();
        
        $this->assertIsArray($result);
        
        // Check that currencies are sorted by code
        $keys = array_keys($result);
        $this->assertEquals(['AUD', 'EUR', 'USD', 'ZAR'], $keys);
    }

    #[Test]
    public function get_all_currencies_returns_fallback_when_api_fails(): void
    {
        // Mock failed HTTP response
        Http::fake([
            'open.er-api.com/*' => Http::response([], 500)
        ]);

        $result = $this->service->getAllCurrencies();
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Should contain fallback currencies
        $this->assertArrayHasKey('CZK', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('GBP', $result);
        
        $this->assertEquals('CZK', $result['CZK']);
        $this->assertEquals('EUR', $result['EUR']);
        $this->assertEquals('USD', $result['USD']);
        $this->assertEquals('GBP', $result['GBP']);
    }

    #[Test]
    public function get_all_currencies_handles_malformed_api_response(): void
    {
        // Mock API response without rates key
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'error' => 'Invalid request'
            ], 200)
        ]);

        $result = $this->service->getAllCurrencies();
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Should return fallback data
        $this->assertArrayHasKey('CZK', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('GBP', $result);
    }

    #[Test]
    public function get_all_currencies_caches_result(): void
    {
        $mockRates = [
            'USD' => 1.0,
            'EUR' => 0.85,
            'CZK' => 21.5
        ];
        
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => $mockRates
            ], 200)
        ]);

        // First call
        $result1 = $this->service->getAllCurrencies();
        
        // Second call should use cache
        $result2 = $this->service->getAllCurrencies();
        
        $this->assertEquals($result1, $result2);
        
        // Verify cache was used by checking that HTTP was only called once
        Http::assertSentCount(1);
    }

    #[Test]
    public function get_all_currencies_logs_errors(): void
    {
        Log::spy();
        
        // Mock exception
        Http::fake([
            'open.er-api.com/*' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $result = $this->service->getAllCurrencies();
        
        // Should log the error
        Log::shouldHaveReceived('error')
            ->once()
            ->with(\Mockery::pattern('/Error fetching currency data/'));
        
        // Should still return fallback data
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function get_common_currencies_returns_subset_of_all_currencies(): void
    {
        // Mock API response with many currencies
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5,
                    'GBP' => 0.73,
                    'PLN' => 3.8,
                    'HUF' => 300,
                    'CHF' => 0.92,
                    'JPY' => 110, // This should not be in common currencies
                    'AUD' => 1.35 // This should not be in common currencies
                ]
            ], 200)
        ]);

        $result = $this->service->getCommonCurrencies();
        
        $this->assertIsArray($result);
        
        // Should contain common currencies
        $this->assertArrayHasKey('CZK', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('GBP', $result);
        $this->assertArrayHasKey('PLN', $result);
        $this->assertArrayHasKey('HUF', $result);
        $this->assertArrayHasKey('CHF', $result);
        
        // Should not contain non-common currencies
        $this->assertArrayNotHasKey('JPY', $result);
        $this->assertArrayNotHasKey('AUD', $result);
        
        // Should be smaller than all currencies
        $allCurrencies = $this->service->getAllCurrencies();
        $this->assertLessThan(count($allCurrencies), count($result));
    }

    #[Test]
    public function get_common_currencies_handles_missing_currencies_in_api(): void
    {
        // Mock API response with only some common currencies
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5
                    // Missing GBP, PLN, HUF, CHF
                ]
            ], 200)
        ]);

        $result = $this->service->getCommonCurrencies();
        
        $this->assertIsArray($result);
        
        // Should contain only available common currencies
        $this->assertArrayHasKey('USD', $result);
        $this->assertArrayHasKey('EUR', $result);
        $this->assertArrayHasKey('CZK', $result);
        
        // Should not contain missing currencies
        $this->assertArrayNotHasKey('GBP', $result);
        $this->assertArrayNotHasKey('PLN', $result);
        $this->assertArrayNotHasKey('HUF', $result);
        $this->assertArrayNotHasKey('CHF', $result);
    }

    #[Test]
    public function get_common_currencies_caches_result(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5,
                    'GBP' => 0.73
                ]
            ], 200)
        ]);

        // First call
        $result1 = $this->service->getCommonCurrencies();
        
        // Second call should use cache (but getAllCurrencies might be called separately)
        $result2 = $this->service->getCommonCurrencies();
        
        $this->assertEquals($result1, $result2);
    }

    #[Test]
    public function methods_handle_api_timeout(): void
    {
        // Mock API timeout
        Http::fake([
            'open.er-api.com/*' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        // getAllCurrencies should return fallback data
        $allCurrencies = $this->service->getAllCurrencies();
        $this->assertIsArray($allCurrencies);
        $this->assertNotEmpty($allCurrencies);
        
        // getCommonCurrencies should work with fallback data
        $commonCurrencies = $this->service->getCommonCurrencies();
        $this->assertIsArray($commonCurrencies);
        
        // Common currencies should be subset of all fallback currencies
        foreach ($commonCurrencies as $code => $value) {
            $this->assertArrayHasKey($code, $allCurrencies);
        }
    }
}
