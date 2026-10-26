<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product\ValueObjects;

use App\Domain\Product\ValueObjects\ProductPrice;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductPriceTest extends TestCase
{
    #[Test]
    public function creates_and_formats_and_string_cast(): void
    {
        $price = new ProductPrice(123.456);
        $this->assertSame(123.46, $price->getValue());
        $this->assertSame('123.46', $price->getFormatted());
        $this->assertSame('123.46', (string) $price);
    }

    #[Test]
    public function zero_and_negative_rules(): void
    {
        $zero = new ProductPrice(0.0);
        $this->assertTrue($zero->isZero());
        $this->expectException(InvalidArgumentException::class);
        new ProductPrice(-0.01);
    }
}
