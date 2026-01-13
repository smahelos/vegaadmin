<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\CurrencyController;
use App\Application\Shared\Money\Contracts\CurrencyApplicationServiceInterface;
use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    private CurrencyController $controller;
    private FakeCurrencyService $currencyService;
    private FakeExchangeService $exchangeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->currencyService = new FakeCurrencyService();
        $this->exchangeService = new FakeExchangeService();
        // Wire exchange delegate so app-layer calls in controller reflect test-configured outcomes
        $this->currencyService->exchange = $this->exchangeService;
        $this->controller = new CurrencyController($this->currencyService);
    }

    #[Test]
    public function controller_instantiates(): void
    {
        $this->assertInstanceOf(CurrencyController::class, $this->controller);
    }

    #[Test]
    public function get_common_currencies(): void
    {
        $this->currencyService->common = ['CZK'=>'CZK','EUR'=>'EUR'];
        $resp = $this->controller->getCommonCurrencies();
        $this->assertEquals(200, $resp->status());
        $this->assertArrayHasKey('CZK', $resp->getData(true));
    }

    #[Test]
    public function get_all_currencies(): void
    {
        $this->currencyService->all = ['USD'=>'USD','GBP'=>'GBP'];
        $resp = $this->controller->getAllCurrencies();
        $this->assertEquals(200, $resp->status());
        $this->assertArrayHasKey('USD', $resp->getData(true));
    }

    #[Test]
    public function get_exchange_rate_success(): void
    {
        $this->currencyService->rate = 1.25;
        $request = Request::create('/api/currencies/exchange-rate','GET',['from'=>'usd','to'=>'eur']);
        $resp = $this->controller->getExchangeRate($request);
        $this->assertEquals(200, $resp->status());
        $this->assertEquals(1.25, $resp->getData(true)['rate']);
    }

    #[Test]
    public function get_exchange_rate_not_available(): void
    {
        $this->currencyService->rate = null;
        $request = Request::create('/api/currencies/exchange-rate','GET',['from'=>'usd','to'=>'eur']);
        $resp = $this->controller->getExchangeRate($request);
        $this->assertEquals(404, $resp->status());
        $this->assertArrayHasKey('error', $resp->getData(true));
    }

    #[Test]
    public function convert_currency_success(): void
    {
        $this->currencyService->rate = 2.0;
        $this->currencyService->converted = 200.0;
        $request = Request::create('/api/currencies/convert','GET',['amount'=>100,'from'=>'usd','to'=>'eur']);
        $resp = $this->controller->convertCurrency($request);
        $this->assertEquals(200, $resp->status());
        $this->assertEquals(200.0, $resp->getData(true)['converted']['amount']);
    }

    #[Test]
    public function convert_currency_not_available(): void
    {
        $this->exchangeService->converted = null;
        $request = Request::create('/api/currencies/convert','GET',['amount'=>50,'from'=>'usd','to'=>'abc']);
        $resp = $this->controller->convertCurrency($request);
        $this->assertEquals(404, $resp->status());
    }
}

class FakeCurrencyService implements CurrencyApplicationServiceInterface
{
    public array $all = [];
    public array $common = [];
    public ?float $rate = null;
    public ?float $converted = null;
    /**
     * Optional delegate to mimic domain exchange service used by application layer.
     */
    public ?FakeExchangeService $exchange = null;

    public function getAllCurrencies(): array { return $this->all; }
    public function getCommonCurrencies(): array { return $this->common; }

    public function getExchangeRate(string $from, string $to): ?float {
        if ($this->rate !== null) {
            return $this->rate;
        }
        return $this->exchange ? $this->exchange->getExchangeRate($from, $to) : null;
    }

    public function convert(float $amount, string $from, string $to): ?float {
        if ($this->converted !== null) {
            return $this->converted;
        }
        return $this->exchange ? $this->exchange->convert($amount, $from, $to) : null;
    }
}

class FakeExchangeService implements CurrencyExchangeServiceInterface
{
    public ?float $rate = null;
    public ?float $converted = null;
    public function getExchangeRate(string $fromCurrency, string $toCurrency): ?float { return $this->rate; }
    public function getExchangeRates(string $baseCurrency = 'USD'): array { return []; }
    public function convert(float $amount, string $fromCurrency, string $toCurrency): ?float { return $this->converted; }
}
