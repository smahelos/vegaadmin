<?php

namespace Tests\Unit\Domain\Shared\Money\ValueObjects;

use App\Domain\Shared\Money\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MoneyTest extends TestCase
{
    #[Test]
    public function can_create_from_float(): void
    {
        $m = Money::fromFloat(123.45, 'USD');
        $this->assertSame('123.45', $m->getAmount());
        $this->assertSame('USD', $m->getCurrency());
    }

    #[Test]
    public function can_create_from_string(): void
    {
        $m = Money::fromString('0010.5000', 'EUR');
        $this->assertSame('10.5', $m->getAmount());
        $this->assertSame('EUR', $m->getCurrency());
    }

    #[Test]
    public function to_float_is_lossy_but_consistent(): void
    {
        $m = Money::fromString('10.50', 'CZK');
        $this->assertSame(10.50, $m->toFloat());
    }

    #[Test]
    public function invalid_currency_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromFloat(10, 'EURO');
    }

    #[Test]
    public function negative_amount_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromFloat(-5, 'USD');
    }

    #[Test]
    public function invalid_string_amount_format_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromString('10,50', 'USD');
    }

    #[Test]
    public function structure_reflection_checks(): void
    {
        $r = new ReflectionClass(Money::class);
        $this->assertTrue($r->hasMethod('fromFloat'));
        $this->assertTrue($r->hasMethod('fromString'));
        $this->assertTrue($r->hasMethod('getAmount'));
        $this->assertTrue($r->hasMethod('getCurrency'));
        $this->assertTrue($r->hasMethod('toFloat'));
    }

    #[Test]
    public function add_two_money_same_currency(): void
    {
        $a = Money::fromString('10.50', 'EUR');
        $b = Money::fromString('2.005', 'EUR');
        $sum = $a->add($b);
        $this->assertSame('12.505', $sum->getAmount());
    }

    #[Test]
    public function subtract_two_money_same_currency(): void
    {
        $a = Money::fromString('15.750', 'EUR');
        $b = Money::fromString('0.75', 'EUR');
        $diff = $a->subtract($b);
        $this->assertSame('15', $diff->getAmount());
    }

    #[Test]
    public function subtract_throws_when_result_negative(): void
    {
        $a = Money::fromString('1.00', 'USD');
        $b = Money::fromString('2.00', 'USD');
        $this->expectException(InvalidArgumentException::class);
        $a->subtract($b);
    }

    #[Test]
    public function currency_mismatch_throws_on_add(): void
    {
        $a = Money::fromString('5', 'USD');
        $b = Money::fromString('5', 'EUR');
        $this->expectException(InvalidArgumentException::class);
        $a->add($b);
    }

    #[Test]
    public function arithmetic_normalizes_trailing_zeros(): void
    {
        $a = Money::fromString('10.5000', 'CZK');
        $b = Money::fromString('0.5000', 'CZK');
        $sum = $a->add($b); // 11.0 -> normalized to 11
        $this->assertSame('11', $sum->getAmount());

        $diff = $sum->subtract(Money::fromString('0.50', 'CZK')); // 10.5
        $this->assertSame('10.5', $diff->getAmount());
    }

    #[Test]
    public function equals_checks_currency_and_amount(): void
    {
        $a = Money::fromString('10.50', 'USD');
        $b = Money::fromString('10.5', 'USD');
        $c = Money::fromString('10.50', 'EUR');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function multiply_by_integer_factor(): void
    {
        $m = Money::fromString('2.05', 'EUR');
        $result = $m->multiply(3); // 6.15
        $this->assertSame('6.15', $result->getAmount());
    }

    #[Test]
    public function multiply_by_zero_returns_zero(): void
    {
        $m = Money::fromString('99.99', 'USD');
        $result = $m->multiply(0);
        $this->assertSame('0', $result->getAmount());
    }

    #[Test]
    public function multiply_negative_factor_throws(): void
    {
        $m = Money::fromString('1', 'USD');
        $this->expectException(InvalidArgumentException::class);
        $m->multiply(-1);
    }

    #[Test]
    public function divide_by_positive_integer(): void
    {
        $m = Money::fromString('10', 'USD');
        $result = $m->divide(4, 4); // 2.5
        $this->assertSame('2.5', $result->getAmount());
    }

    #[Test]
    public function divide_with_fraction_extension(): void
    {
        $m = Money::fromString('1', 'USD');
        $result = $m->divide(3, 6); // 0.333333
        $this->assertSame('0.333333', $result->getAmount());
    }

    #[Test]
    public function divide_invalid_divisor_throws(): void
    {
        $m = Money::fromString('5', 'USD');
        $this->expectException(InvalidArgumentException::class);
        $m->divide(0);
    }

    #[Test]
    public function divide_invalid_scale_throws(): void
    {
        $m = Money::fromString('5', 'USD');
        $this->expectException(InvalidArgumentException::class);
        $m->divide(2, 10); // scale > 8
    }

    // Rounding strategy tests ------------------------------------------------

    #[Test]
    public function divide_rounding_half_up_simple(): void
    {
        $m = Money::fromString('1', 'USD');
        $r = $m->divideRounding(2, 2, Money::ROUND_HALF_UP); // 0.50 -> normalized 0.5
        $this->assertSame('0.5', $r->getAmount());
    }

    #[Test]
    public function divide_rounding_half_up_tie_rounds_up(): void
    {
        $m = Money::fromString('1', 'USD'); // 1 / 8 = 0.125 -> 0.13 HALF_UP
        $r = $m->divideRounding(8, 2, Money::ROUND_HALF_UP);
        $this->assertSame('0.13', $r->getAmount());
    }

    #[Test]
    public function divide_rounding_bankers_tie_even_stays(): void
    {
        $m = Money::fromString('1', 'USD'); // 1 / 8 = 0.125 -> 0.12 BANKERS (2 is even)
        $r = $m->divideRounding(8, 2, Money::ROUND_BANKERS);
        $this->assertSame('0.12', $r->getAmount());
    }

    #[Test]
    public function divide_rounding_bankers_tie_odd_rounds_up(): void
    {
        $m = Money::fromString('135', 'USD'); // 135 / 1000 = 0.135 -> 0.14 (3 odd)
        $r = $m->divideRounding(1000, 2, Money::ROUND_BANKERS);
        $this->assertSame('0.14', $r->getAmount());
    }

    #[Test]
    public function divide_rounding_scale_zero_half_up_vs_bankers_even(): void
    {
        $m = Money::fromString('5', 'USD'); // 5 / 2 = 2.5
        $halfUp = $m->divideRounding(2, 0, Money::ROUND_HALF_UP); // -> 3
        $bankers = $m->divideRounding(2, 0, Money::ROUND_BANKERS); // 2 even -> 2
        $this->assertSame('3', $halfUp->getAmount());
        $this->assertSame('2', $bankers->getAmount());
    }

    #[Test]
    public function divide_rounding_scale_zero_bankers_odd_rounds_up(): void
    {
        $m = Money::fromString('15', 'USD'); // 15 / 2 = 7.5 (7 odd)->8
        $bankers = $m->divideRounding(2, 0, Money::ROUND_BANKERS);
        $this->assertSame('8', $bankers->getAmount());
    }

    #[Test]
    public function divide_rounding_exact_half_with_leading_zeros(): void
    {
        $m = Money::fromString('1', 'USD'); // 1 / 200 = 0.005 -> HALF_UP 0.01, BANKERS 0
        $halfUp = $m->divideRounding(200, 2, Money::ROUND_HALF_UP);
        $bankers = $m->divideRounding(200, 2, Money::ROUND_BANKERS);
        $this->assertSame('0.01', $halfUp->getAmount());
        $this->assertSame('0', $bankers->getAmount());
    }
}
