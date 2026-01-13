<?php

namespace Tests\Unit\Domain\Product\ValueObjects;

use App\Domain\Product\ValueObjects\ProductSku;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductSkuTest extends TestCase
{
	#[Test]
	public function normalization_and_string_cast(): void
	{
		$sku = new ProductSku('  abc-123  ');
		$this->assertSame('ABC-123', $sku->value());
		$this->assertSame('ABC-123', (string)$sku);
	}

	#[Test]
	public function factory_method(): void
	{
		$sku = ProductSku::fromString('x_y');
		$this->assertEquals('X_Y', $sku->value());
	}

	#[Test]
	public function length_and_start_rules(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new ProductSku('ab');
	}

	#[Test]
	public function rejects_starting_with_digit(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new ProductSku('1ABC');
	}

	#[Test]
	public function rejects_consecutive_separators(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new ProductSku('AB--CD');
	}

	#[Test]
	public function equality_case_insensitive(): void
	{
		$a = new ProductSku('code_1');
		$b = new ProductSku('CODE_1');
		$this->assertTrue($a->equals($b));
	}
}
