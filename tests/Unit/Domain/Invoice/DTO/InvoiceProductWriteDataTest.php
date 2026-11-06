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

        $base = $line->base();
        $this->assertSame('224.01', $base->getAmount());
        $taxAmount = $line->taxAmount();
        $this->assertSame('47.04', $taxAmount->getAmount());
        $total = $line->total();
        $this->assertSame('271.05', $total->getAmount());
        $this->assertSame('CZK', $total->getCurrency());
    }

    #[Test]
    public function keeps_a_decimal_quantity(): void
    {
        $line = $this->line(
            quantity: '1.5',
            price: '249.30',
            taxRate: '12',
        );

        $base = $line->base();
        $this->assertSame('373.95', $base->getAmount());
        $taxAmount = $line->taxAmount();
        $this->assertSame('44.87', $taxAmount->getAmount());
        $total = $line->total();
        $this->assertSame('418.82', $total->getAmount());
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

        $base = $line->base();
        $this->assertSame('0.33', $base->getAmount());
        $taxAmount = $line->taxAmount();
        $this->assertSame('0.07', $taxAmount->getAmount());
    }

    #[Test]
    public function a_zero_rate_line_has_no_tax(): void
    {
        $line = $this->line(
            quantity: '2',
            price: '100',
            taxRate: '0',
        );

        $taxAmount = $line->taxAmount();
        $this->assertSame('0', $taxAmount->getAmount());
        $total = $line->total();
        $this->assertSame('200', $total->getAmount());
    }

    #[Test]
    public function a_line_without_a_product_is_custom(): void
    {
        $line = $this->line();
        $this->assertTrue($line->isCustomProduct());
        $line = $this->line(
            productId: ProductId::fromInt(7),
        );
        $this->assertFalse(
            $line->isCustomProduct(),
        );
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
