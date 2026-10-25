<?php

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyControllerFeatureTest extends TestCase
{
    #[Test]
    public function it_returns_common_currencies(): void
    {
        Http::fake(); // forces fallback inside CurrencyService
        Cache::forget('currencies_common');
        $resp = $this->getJson(route('api.currencies.common'));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertArrayHasKey('CZK', $data);
        $this->assertArrayHasKey('EUR', $data);
    }

    #[Test]
    public function it_returns_all_currencies(): void
    {
        Http::fake(['open.er-api.com/*' => Http::response([
            'rates' => [
                'USD' => 1,
                'CZK' => 22.0,
                'EUR' => 0.9,
            ],
        ], 200)]);
        Cache::forget('currencies_all');
        $resp = $this->getJson(route('api.currencies.all'));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertArrayHasKey('USD', $data);
        $this->assertArrayHasKey('CZK', $data);
    }

    #[Test]
    public function it_returns_exchange_rate(): void
    {
        Http::fake(); // cause service to fallback to deterministic exchange service or internal handling
        $resp = $this->getJson(route('api.exchange-rate', ['from' => 'USD', 'to' => 'USD']));
        $resp->assertOk()->assertJson([
            'from' => 'USD',
            'to' => 'USD',
            'rate' => 1.0,
        ]);
    }

    #[Test]
    public function exchange_rate_of_a_currency_without_a_rate_is_not_found(): void
    {
        $resp = $this->getJson(route('api.exchange-rate', ['from' => 'USD', 'to' => 'XYZ']));

        $resp->assertNotFound();
    }

    #[Test]
    public function conversion_to_a_currency_without_a_rate_is_not_found(): void
    {
        $resp = $this->getJson(route('api.convert-currency', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'XYZ',
        ]));

        $resp->assertNotFound();
    }

    #[Test]
    public function it_validates_exchange_rate_params(): void
    {
        $resp = $this->getJson(route('api.exchange-rate')); // missing params
        $resp->assertStatus(422);
    }

    #[Test]
    public function it_converts_currency_amount(): void
    {
        Http::fake();
        $resp = $this->getJson(route('api.convert-currency', [
            'amount' => 100,
            'from' => 'USD',
            'to' => 'USD',
        ]));
        $resp->assertOk();
        // Amount may be int or float depending on service implementation
        $this->assertEquals(100.0, $resp->json('original.amount'));
        $this->assertEquals(100.0, $resp->json('converted.amount'));
    }

    #[Test]
    public function it_validates_convert_currency_params(): void
    {
        $resp = $this->getJson(route('api.convert-currency', [
            'amount' => 100,
            'from' => 'US',
            'to' => 'EUR',
        ]));
        $resp->assertStatus(422);
    }
}
