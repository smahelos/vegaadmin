<?php

namespace Tests\Feature\Shared\Money;

use App\Domain\Shared\Money\Services\CurrencyExchangeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CurrencyExchangeCircuitBreakerTest extends TestCase
{
    private CurrencyExchangeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CurrencyExchangeService::class);
        Cache::forget('fx_cb_state');
        Cache::forget('fx_cb_fail_count');
        Cache::forget('fx_cb_opened_at');
    }

    #[Test]
    public function transitions_to_open_after_fail_threshold(): void
    {
        config()->set('exchange.fail_threshold', 2);
        config()->set('exchange.open_timeout_seconds', 60);

        Http::fakeSequence()
            ->pushStatus(500)
            ->pushStatus(500)
            ->pushStatus(200, ['rates' => ['USD' => 1.0, 'EUR' => 0.9]]);

        $fallback1 = $this->service->getExchangeRates('USD'); // fail 1 -> fallback
        $this->assertIsArray($fallback1);
        $fallback2 = $this->service->getExchangeRates('USD'); // fail 2 -> circuit opens, still fallback
        $this->assertIsArray($fallback2);

        $this->assertEquals('open', Cache::get('fx_cb_state'));
        $this->assertNotNull(Cache::get('fx_cb_opened_at'));

        // Third call should short-circuit (success response in queue should remain unused) → still fallback
        $third = $this->service->getExchangeRates('USD');
        $this->assertIsArray($third);
    }

    #[Test]
    public function half_open_probe_resets_circuit_on_success(): void
    {
    // We simulate directly the HALF_OPEN state to avoid reliance on cache TTL vs synthetic clock.
    Cache::put('fx_cb_state', 'half_open', 60);
    Cache::put('fx_cb_fail_count', 1, 60);

        // Successful probe response
        Http::fake(fn() => Http::response(['rates' => ['USD' => 1.0, 'CZK' => 22.2]], 200));

        $rates = $this->service->getExchangeRates('USD');
        $this->assertIsArray($rates);
        $this->assertEquals(22.2, $rates['CZK']);
        $this->assertEquals('closed', Cache::get('fx_cb_state'));
    }

    #[Test]
    public function half_open_probe_failure_reopens_circuit(): void
    {
        config()->set('exchange.fail_threshold', 1);
        config()->set('exchange.open_timeout_seconds', 1);

        Http::fakeSequence()
            ->pushStatus(500) // initial -> open
            ->pushStatus(500); // half-open probe fails
        $this->service->getExchangeRates('USD'); // open (fallback)
        $this->assertEquals('open', Cache::get('fx_cb_state'));

        // Wait for open timeout expiry
        sleep(2);

        // Probe attempt
        $this->service->getExchangeRates('USD'); // half-open probe failure -> reopen
        $this->assertEquals('open', Cache::get('fx_cb_state'));
    }
}
