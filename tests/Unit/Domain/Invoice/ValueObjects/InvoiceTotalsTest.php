<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceTotalsTest extends TestCase
{
    #[Test]
    public function sums_the_rounded_lines(): void
    {
        $totals = InvoiceTotals::fromProducts(Currency::CZK, [
            $this->line(
                '3',
                '74.67',
                'CZK',
            ),
            $this->line(
                '1',
                '149.83',
                'CZK',
            ),
            $this->line(
                '2',
                '248.88',
                'CZK',
            ),
            $this->line(
                '1',
                '3235.49',
                'CZK',
            ),
        ]);

        $this->assertSame('4107.09', $totals->subtotal->getAmount());
        $this->assertSame('862.48', $totals->tax->getAmount());
        $this->assertSame('4969.57', $totals->total()->getAmount());
        $this->assertSame('CZK', $totals->total()->getCurrency());
    }

    #[Test]
    public function an_invoice_without_lines_totals_zero(): void
    {
        $totals = InvoiceTotals::fromProducts(Currency::EUR, []);

        $this->assertSame('0', $totals->total()->getAmount());
        $this->assertSame('EUR', $totals->total()->getCurrency());
    }

    #[Test]
    public function rejects_a_line_in_another_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceTotals::fromProducts(Currency::CZK, [$this->line('1', '10', 'EUR')]);
    }

    private function line(
        string $quantity,
        string $price,
        string $currency,
    ): InvoiceProductWriteData {
        return new InvoiceProductWriteData(
            name: 'Item',
            quantity: $quantity,
            unit: Unit::PIECES,
            price: Money::fromString($price, $currency),
            taxRate: '21',
        );
    }
}
