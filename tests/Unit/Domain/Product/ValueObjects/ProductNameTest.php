<?php

namespace Tests\Unit\Domain\Product\ValueObjects;

use App\Domain\Product\ValueObjects\ProductName;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProductNameTest extends TestCase
{
    #[Test]
    public function trims_and_string_cast(): void
    {
        $name = new ProductName('  Test Product  ');
        $this->assertSame('Test Product', $name->getValue());
        $this->assertSame('Test Product', (string)$name);
    }

    #[Test]
    public function from_string_factory(): void
    {
        $name = ProductName::fromString('Another Product');
        $this->assertEquals('Another Product', $name->getValue());
    }

    #[Test]
    public function empty_or_whitespace_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProductName('   ');
    }

    #[Test]
    public function too_long_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProductName(str_repeat('A', 256));
    }

    #[Test]
    public function equality_checks(): void
    {
        $a = new ProductName('Same');
        $b = new ProductName('Same');
        $c = new ProductName('Diff');
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    #[Test]
    public function max_length_allowed(): void
    {
        $max = str_repeat('a', 255);
        $name = new ProductName($max);
        $this->assertEquals($max, $name->getValue());
    }
}
