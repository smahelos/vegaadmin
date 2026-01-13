<?php

namespace Tests\Unit\Domain\Shared\Money\ValueObjects;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Money\Contracts\CurrencyExchangeServiceInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Mockery;

class MoneyConversionTest extends TestCase
{
    /** @var \Mockery\MockInterface&CurrencyExchangeServiceInterface */
    private $exchange; // Mockery mock

    protected function setUp(): void
    {
    parent::setUp();
    // @phpstan-ignore-next-line
    $this->exchange = Mockery::mock(CurrencyExchangeServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function convert_same_currency_returns_self_amount(): void
    {
        $m = Money::fromString('10.50', 'EUR');
        $copy = $m->convertTo('EUR', $this->exchange);
        $this->assertTrue($m->equals($copy));
    }

    #[Test]
    public function convert_uses_exchange_rate_half_up(): void
    {
        $m = Money::fromString('10', 'EUR');
        $this->exchange->shouldReceive('getExchangeRate')->once()->with('EUR', 'USD')->andReturn(1.2345);
        $converted = $m->convertTo('USD', $this->exchange, 2, Money::ROUND_HALF_UP);
        $this->assertSame('12.35', $converted->getAmount()); // 10 * 1.2345 = 12.345 -> 12.35
    }

    #[Test]
    public function convert_uses_exchange_rate_bankers(): void
    {
        $m = Money::fromString('10', 'EUR');
        $this->exchange->shouldReceive('getExchangeRate')->once()->with('EUR', 'USD')->andReturn(1.2355); // 12.355 -> bankers tie (.355 -> rounding digit 5, previous 5 odd?)
        $converted = $m->convertTo('USD', $this->exchange, 2, Money::ROUND_BANKERS);
        // 12.355 bankers -> 12.36 because previous kept digit is 5 (odd)
        $this->assertSame('12.36', $converted->getAmount());
    }

    #[Test]
    public function convert_invalid_rate_throws(): void
    {
        $m = Money::fromString('5', 'EUR');
        $this->exchange->shouldReceive('getExchangeRate')->once()->with('EUR', 'USD')->andReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $m->convertTo('USD', $this->exchange);
    }

    #[Test]
    public function convert_scale_zero(): void
    {
        $m = Money::fromString('2', 'EUR');
        $this->exchange->shouldReceive('getExchangeRate')->once()->with('EUR', 'USD')->andReturn(1.75); // 3.5 -> scale0 HALF_UP -> 4
        $converted = $m->convertTo('USD', $this->exchange, 0, Money::ROUND_HALF_UP);
        $this->assertSame('4', $converted->getAmount());
    }
}
