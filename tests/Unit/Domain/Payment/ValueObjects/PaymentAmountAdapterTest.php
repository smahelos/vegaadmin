<?php

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\Adapters\PaymentAmountAdapter;
use App\Domain\Payment\ValueObjects\PaymentAmount;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentAmountAdapterTest extends TestCase
{
	#[Test]
	public function converts_value_object_to_array(): void
	{
		$vo = new PaymentAmount(123.45, 'EUR');
		$adapter = new PaymentAmountAdapter();
		$arr = $adapter->toArray($vo);
		$this->assertSame(123.45, $arr['amount']);
		$this->assertSame('EUR', $arr['currency']);
		$this->assertSame('123.45', $arr['formatted']);
		$this->assertSame(12345, $arr['cents']);
	}
}
