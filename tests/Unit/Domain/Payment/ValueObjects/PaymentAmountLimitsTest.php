<?php

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\ValueObjects\PaymentAmountLimits;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentAmountLimitsTest extends TestCase
{
    #[Test]
    public function exposes_supported_currencies(): void
    {
        $currencies = PaymentAmountLimits::supportedCurrencies();
        $this->assertContains('CZK', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('USD', $currencies);
    }

    #[Test]
    public function returns_min_amount(): void
    {
        $this->assertSame(0.01, PaymentAmountLimits::minAmount());
    }

    #[Test]
    public function gives_max_for_currency(): void
    {
        $this->assertSame(10000000.0, PaymentAmountLimits::maxFor('czk'));
        $this->assertSame(500000.0, PaymentAmountLimits::maxFor('eur'));
    }

    #[Test]
    public function throws_for_unknown_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PaymentAmountLimits::maxFor('ABC');
    }
}
