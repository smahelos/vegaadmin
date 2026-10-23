<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceProductWriteDataTest extends TestCase
{
    #[Test]
    public function computes_base_tax_and_total_of_a_line_in_cents(): void
    {
        $line = $this->line(
            quantity: '3',
            price: '74.67',
            taxRate: '21',
        );

        $this->assertSame('224.01', $line->base()->getAmount());
        $this->assertSame('47.04', $line->taxAmount()->getAmount());
        $this->assertSame('271.05', $line->total()->getAmount());
        $this->assertSame('CZK', $line->total()->getCurrency());
    }

    #[Test]
    public function keeps_a_decimal_quantity(): void
    {
        $line = $this->line(
            quantity: '1.5',
            price: '249.30',
            taxRate: '12',
        );

        $this->assertSame('373.95', $line->base()->getAmount());
        $this->assertSame('44.87', $line->taxAmount()->getAmount());
        $this->assertSame('418.82', $line->total()->getAmount());
    }

    #[Test]
    public function computes_the_tax_from_the_rounded_base(): void
    {
        // 0.333 * 1.00 = 0.333 -> base 0.33; 21 % of 0.33 = 0.0693 -> 0.07
        $line = $this->line(
            quantity: '0.333',
            price: '1',
            taxRate: '21',
        );

        $this->assertSame('0.33', $line->base()->getAmount());
        $this->assertSame('0.07', $line->taxAmount()->getAmount());
    }

    #[Test]
    public function a_zero_rate_line_has_no_tax(): void
    {
        $line = $this->line(
            quantity: '2',
            price: '100',
            taxRate: '0',
        );

        $this->assertSame('0', $line->taxAmount()->getAmount());
        $this->assertSame('200', $line->total()->getAmount());
    }

    #[Test]
    public function a_line_without_a_product_is_custom(): void
    {
        $this->assertTrue($this->line()->isCustomProduct());
        $this->assertFalse($this->line(productId: ProductId::fromInt(7))->isCustomProduct());
    }

    private function line(
        string $quantity = '1',
        string $price = '100',
        string $taxRate = '21',
        ?ProductId $productId = null,
    ): InvoiceProductWriteData {
        return new InvoiceProductWriteData(
            name: 'Item',
            quantity: $quantity,
            unit: Unit::PIECES,
            price: Money::fromString($price, 'CZK'),
            taxRate: $taxRate,
            productId: $productId,
        );
    }
}
