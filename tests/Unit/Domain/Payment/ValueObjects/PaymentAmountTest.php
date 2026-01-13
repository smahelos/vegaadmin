<?php

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\ValueObjects\PaymentAmount;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentAmount value object (pure value logic - no framework dependencies)
 */
class PaymentAmountTest extends TestCase
{
	#[Test]
	public function create_valid_amount(): void
	{
		$amount = new PaymentAmount(100.25, 'czk');
		$this->assertEquals(100.25, $amount->getAmount());
		$this->assertEquals('CZK', $amount->getCurrency());
		$this->assertEquals('100.25', $amount->getFormattedAmount());
		$this->assertEquals(10025, $amount->getAmountInCents());
		$this->assertFalse($amount->isZero());
	}

	#[Test]
	public function from_string_factory(): void
	{
		$amount = PaymentAmount::fromString('50.5', 'EUR');
		$this->assertEquals(50.5, $amount->getAmount());
		$this->assertEquals('EUR', $amount->getCurrency());
	}

	#[Test]
	public function rejects_zero_or_negative(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new PaymentAmount(0.0, 'CZK');
	}

	#[Test]
	public function rejects_unsupported_currency(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new PaymentAmount(10, 'GBP');
	}

	#[Test]
	public function rejects_amount_over_limit(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new PaymentAmount(10000001, 'CZK');
	}

	#[Test]
	public function add_and_subtract_same_currency(): void
	{
		$a = new PaymentAmount(100, 'CZK');
		$b = new PaymentAmount(50, 'CZK');
		$sum = $a->add($b);
		$this->assertEquals(150, $sum->getAmount());
		$diff = $a->subtract($b);
		$this->assertEquals(50, $diff->getAmount());
	}

	#[Test]
	public function add_different_currency_throws(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$a = new PaymentAmount(10, 'CZK');
		$b = new PaymentAmount(5, 'EUR');
		$a->add($b);
	}

	#[Test]
	public function equality_and_comparison(): void
	{
		$a = new PaymentAmount(10.0001, 'USD');
		$b = new PaymentAmount(10.0002, 'USD');
		$this->assertTrue($a->equals($b)); // difference below threshold
		$c = new PaymentAmount(9.99, 'USD');
		$this->assertTrue($a->greaterThan($c));
	}
}
