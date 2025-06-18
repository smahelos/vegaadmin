<?php

namespace Tests\Feature\Services;

use App\Services\CurrencyExchangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyExchangeServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private CurrencyExchangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyExchangeService();
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CurrencyExchangeService::class, $this->service);
    }

    #[Test]
    public function get_exchange_rate_returns_one_for_same_currencies(): void
    {
        $rate = $this->service->getExchangeRate('USD', 'USD');
        
        $this->assertEquals(1.0, $rate);
    }

    #[Test]
    public function get_exchange_rate_calculates_correct_rate(): void
    {
        // Mock successful API response
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5
                ]
            ], 200)
        ]);

        $rate = $this->service->getExchangeRate('USD', 'EUR');
        
        $this->assertIsFloat($rate);
        $this->assertEquals(0.85, $rate); // EUR rate relative to USD
    }

    #[Test]
    public function get_exchange_rate_handles_cross_currency_conversion(): void
    {
        // Mock successful API response
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5
                ]
            ], 200)
        ]);

        $rate = $this->service->getExchangeRate('EUR', 'CZK');
        
        $this->assertIsFloat($rate);
        // CZK/EUR = 21.5/0.85 ≈ 25.29
        $expectedRate = 21.5 / 0.85;
        $this->assertEqualsWithDelta($expectedRate, $rate, 0.01);
    }

    #[Test]
    public function get_exchange_rate_returns_null_for_missing_currency(): void
    {
        // Mock API response without one of the currencies
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85
                ]
            ], 200)
        ]);

        $rate = $this->service->getExchangeRate('USD', 'INVALID');
        
        $this->assertNull($rate);
    }

    #[Test]
    public function get_exchange_rates_returns_api_data(): void
    {
        $mockRates = [
            'USD' => 1.0,
            'EUR' => 0.85,
            'CZK' => 21.5,
            'GBP' => 0.73
        ];

        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => $mockRates
            ], 200)
        ]);

        $rates = $this->service->getExchangeRates();
        
        $this->assertIsArray($rates);
        $this->assertEquals($mockRates, $rates);
    }

    #[Test]
    public function get_exchange_rates_returns_fallback_when_api_fails(): void
    {
        // Mock failed API response
        Http::fake([
            'open.er-api.com/*' => Http::response([], 500)
        ]);

        $rates = $this->service->getExchangeRates();
        
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
        
        // Should contain fallback currencies
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('CZK', $rates);
        $this->assertEquals(1.0, $rates['USD']);
    }

    #[Test]
    public function get_exchange_rates_handles_malformed_api_response(): void
    {
        // Mock API response without rates key
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'error' => 'Invalid base currency'
            ], 200)
        ]);

        $rates = $this->service->getExchangeRates();
        
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
        
        // Should return fallback data
        $this->assertArrayHasKey('USD', $rates);
        $this->assertEquals(1.0, $rates['USD']);
    }

    #[Test]
    public function get_exchange_rates_uses_custom_base_currency(): void
    {
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.18,
                    'CZK' => 25.3,
                    'EUR' => 1.0
                ]
            ], 200)
        ]);

        $rates = $this->service->getExchangeRates('EUR');
        
        Http::assertSent(function ($request) {
            return $request->url() === 'https://open.er-api.com/v6/latest?base=EUR';
        });
        
        $this->assertIsArray($rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('CZK', $rates);
    }

    #[Test]
    public function get_exchange_rates_caches_result(): void
    {
        $mockRates = ['USD' => 1.0, 'EUR' => 0.85];
        
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => $mockRates
            ], 200)
        ]);

        // First call
        $rates1 = $this->service->getExchangeRates();
        
        // Second call should use cache
        $rates2 = $this->service->getExchangeRates();
        
        $this->assertEquals($rates1, $rates2);
        
        // Verify cache was used by checking that HTTP was only called once
        Http::assertSentCount(1);
    }

    #[Test]
    public function get_exchange_rates_logs_errors(): void
    {
        Log::spy();
        
        // Mock exception
        Http::fake([
            'open.er-api.com/*' => function () {
                throw new \Exception('Connection timeout');
            }
        ]);

        $rates = $this->service->getExchangeRates();
        
        // Should log the error
        Log::shouldHaveReceived('error')
            ->once()
            ->with(\Mockery::pattern('/Error fetching currency exchange rates/'));
        
        // Should still return fallback data
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
    }

    #[Test]
    public function convert_calculates_correct_amount(): void
    {
        // Mock API response
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85,
                    'CZK' => 21.5
                ]
            ], 200)
        ]);

        $result = $this->service->convert(100.0, 'USD', 'EUR');
        
        $this->assertIsFloat($result);
        $this->assertEquals(85.0, $result); // 100 USD * 0.85 = 85 EUR
    }

    #[Test]
    public function convert_rounds_to_two_decimal_places(): void
    {
        // Mock API response with rates that would produce decimals
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.857123
                ]
            ], 200)
        ]);

        $result = $this->service->convert(100.0, 'USD', 'EUR');
        
        $this->assertIsFloat($result);
        $this->assertEquals(85.71, $result); // Should be rounded to 2 decimal places
    }

    #[Test]
    public function convert_returns_null_for_invalid_currencies(): void
    {
        // Mock API response without one of the currencies
        Http::fake([
            'open.er-api.com/*' => Http::response([
                'rates' => [
                    'USD' => 1.0,
                    'EUR' => 0.85
                ]
            ], 200)
        ]);

        $result = $this->service->convert(100.0, 'USD', 'INVALID');
        
        $this->assertNull($result);
    }

    #[Test]
    public function convert_handles_same_currency(): void
    {
        $result = $this->service->convert(100.0, 'USD', 'USD');
        
        $this->assertEquals(100.0, $result);
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

        // getExchangeRates should return fallback data
        $rates = $this->service->getExchangeRates();
        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
        
        // getExchangeRate should work with fallback data
        $rate = $this->service->getExchangeRate('USD', 'EUR');
        $this->assertIsFloat($rate);
        
        // convert should work with fallback data
        $result = $this->service->convert(100.0, 'USD', 'EUR');
        $this->assertIsFloat($result);
    }
}
