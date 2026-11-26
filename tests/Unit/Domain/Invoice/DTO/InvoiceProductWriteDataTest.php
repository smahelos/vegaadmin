<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceProductWriteDataTest extends TestCase
{
    #[Test]
    public function rounds_the_base_of_a_line_to_cents(): void
    {
        $line = $this->line(
            quantity: '3',
            price: '74.67',
        );
        $base = $line->base();
        $this->assertSame('224.01', $base->amount);
        $this->assertSame(Currency::CZK, $base->currency);

        // 0.333 * 1.00 = 0.333 -> 0.33
        $line = $this->line(
            quantity: '0.333',
            price: '1',
        );
        $base = $line->base();
        $this->assertSame('0.33', $base->amount);
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
        ?ProductId $productId = null,
    ): InvoiceProductWriteData {
        return new InvoiceProductWriteData(
            name: 'Item',
            quantity: $quantity,
            unit: Unit::PIECES,
            price: Money::fromString($price, Currency::CZK),
            taxRate: '21',
            productId: $productId,
        );
    }
}
