<?php

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\ValueObjects\PaymentAmount;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentAmountFromMoneyTest extends TestCase
{
    #[Test]
    public function creates_payment_amount_from_money(): void
    {
        $money = Money::fromString('123.4567', 'EUR');
        $pa = PaymentAmount::fromMoney($money);
        $this->assertSame('EUR', $pa->getCurrency());
        $this->assertSame('123.46', $pa->getFormattedAmount()); // rounded to 2 decimals via number_format
    }
}
