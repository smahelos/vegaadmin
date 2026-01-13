<?php

namespace Tests\Unit\Domain\Product\ValueObjects;

use App\Domain\Product\ValueObjects\ProductPrice;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    #[Test]
    public function creates_and_formats_and_string_cast(): void
    {
        $price = new ProductPrice(123.456);
        $this->assertSame(123.46, $price->getValue());
        $this->assertSame('123.46', $price->getFormatted());
        $this->assertSame('123.46', (string)$price);
    }

    #[Test]
    public function factory_from_float_and_string(): void
    {
        $this->assertSame(25.5, ProductPrice::fromFloat(25.50)->getValue());
        $this->assertSame(15.75, ProductPrice::fromString('15.75')->getValue());
    }

    #[Test]
    public function zero_and_negative_rules(): void
    {
        $zero = new ProductPrice(0.0);
        $this->assertTrue($zero->isZero());
        $this->expectException(InvalidArgumentException::class);
        new ProductPrice(-0.01);
    }

    #[Test]
    public function arithmetic_and_equality(): void
    {
        $a = new ProductPrice(10.00);
        $b = ProductPrice::fromFloat(10.0);
        $this->assertTrue($a->equals($b));

        $sum = $a->add(new ProductPrice(2.50));
        $this->assertSame('12.50', (string)$sum);

        $diff = $sum->subtract(new ProductPrice(2.50));
        $this->assertSame('10.00', (string)$diff);

        $multiplied = $a->multiply(2.25);
        $this->assertSame('22.50', (string)$multiplied);
    }
}
