<?php

namespace Tests\Feature\Domain\Shared\Money;

use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Support\Traits\UsesDeterministicExchange;

/**
 * Feature test verifying deterministic FX stub integration.
 */
class DeterministicExchangeFeatureTest extends TestCase
{
    use RefreshDatabase;
    use UsesDeterministicExchange;

    protected function setUp(): void
    {
        parent::setUp();
    $this->setUpDeterministicExchange(); // sets config flag for provider swap
    }

    #[Test]
    public function container_resolves_stubbed_exchange_service(): void
    {
        $service = app(CurrencyExchangeServiceInterface::class);
        $this->assertInstanceOf(CurrencyExchangeServiceInterface::class, $service);
        $this->assertSame(1.0, $service->getExchangeRate('USD', 'USD'));
    }

    #[Test]
    public function money_conversion_uses_deterministic_rates(): void
    {
        $eur = Money::fromString('100', 'EUR'); // baseRates: EUR=0.8, USD=1.0 => USD/EUR = 1.0/0.8 = 1.25
        $usd = $eur->convertTo('USD', app(CurrencyExchangeServiceInterface::class));
        $this->assertSame('125', $usd->getAmount());

        $czk = $usd->convertTo('CZK', app(CurrencyExchangeServiceInterface::class)); // CZK/USD = 20 / 1.0 = 20 -> 125 *20 = 2500
        $this->assertSame('2500', $czk->getAmount());
    }

    #[Test]
    public function unknown_currency_returns_null_rate(): void
    {
        $service = app(CurrencyExchangeServiceInterface::class);
        $this->assertNull($service->getExchangeRate('EUR', 'XYZ'));
    }
}
