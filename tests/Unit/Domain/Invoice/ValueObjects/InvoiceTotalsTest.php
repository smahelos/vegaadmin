<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\NonPayerVatRateException;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Invoice\ValueObjects\VatLine;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceTotalsTest extends TestCase
{
    #[Test]
    public function rounds_the_vat_of_a_rate_once_from_its_unrounded_base(): void
    {
        // Each line: 0.333 x 1 = 0.333 at 21 %; per line it would be 0.33 + 0.07 three times.
        $line = $this->line(
            '0.333',
            '1',
            '21',
        );

        $totals = InvoiceTotals::fromLines(
            Currency::CZK,
            [
                $line,
                $line,
                $line,
            ],
        );

        $this->assertSame(
            ['21'],
            $this->rates($totals),
        );
        $vatLine = $totals->vatLines[0];
        // 0.999 -> 1.00; 0.999 x 21 % = 0.20979 -> 0.21
        $this->assertSame('1', $vatLine->base->amount);
        $this->assertSame('0.21', $vatLine->vat->amount);
        $vatLineTotal = $vatLine->total();
        $this->assertSame('1.21', $vatLineTotal->amount);
    }

    #[Test]
    public function rounds_the_vat_of_the_sum_not_of_the_lines(): void
    {
        // 0.05 x 21 % = 0.0105 rounds to 0.01 per line, but ten lines give 0.105 -> 0.11.
        $lines = array_fill(
            0,
            10,
            $this->line(
                '1',
                '0.05',
                '21',
            ),
        );

        $totals = InvoiceTotals::fromLines(Currency::CZK, $lines);

        $this->assertSame('0.5', $totals->subtotal->amount);
        $this->assertSame('0.11', $totals->tax->amount);
    }

    #[Test]
    public function sums_the_rounded_rates_from_the_highest(): void
    {
        $totals = InvoiceTotals::fromLines(
            Currency::CZK,
            [
                $this->line(
                    '1',
                    '100',
                    '0',
                ),
                $this->line(
                    '3',
                    '74.67',
                    '21',
                ),
                $this->line(
                    '1.5',
                    '249.30',
                    '12',
                ),
                $this->line(
                    '1',
                    '3235.49',
                    '21',
                ),
            ],
        );

        $this->assertSame(
            [
                '21',
                '12',
                '0',
            ],
            $this->rates($totals),
        );
        [$standard, $reduced, $zero] = $totals->vatLines;
        $this->assertSame('3459.5', $standard->base->amount);
        $this->assertSame('726.5', $standard->vat->amount);
        $this->assertSame('373.95', $reduced->base->amount);
        $this->assertSame('44.87', $reduced->vat->amount);
        $this->assertSame('100', $zero->base->amount);
        $this->assertSame('0', $zero->vat->amount);
        $this->assertSame('3933.45', $totals->subtotal->amount);
        $this->assertSame('771.37', $totals->tax->amount);
        $total = $totals->total();
        $this->assertSame('4704.82', $total->amount);
        $this->assertSame(Currency::CZK, $total->currency);
    }

    #[Test]
    public function one_rate_written_differently_is_one_vat_line(): void
    {
        $totals = InvoiceTotals::fromLines(
            Currency::CZK,
            [
                $this->line(
                    '1',
                    '100',
                    '21.00',
                ),
                $this->line(
                    '1',
                    '100',
                    '21',
                ),
                $this->line(
                    '1',
                    '100',
                    '10.50',
                ),
                $this->line(
                    '1',
                    '100',
                    '0.00',
                ),
                $this->line(
                    '1',
                    '100',
                    '0',
                ),
            ],
        );

        $this->assertSame(
            [
                '21',
                '10.5',
                '0',
            ],
            $this->rates($totals),
        );
        $this->assertSame('200', $totals->vatLines[0]->base->amount);
        $this->assertSame('42', $totals->vatLines[0]->vat->amount);
        $this->assertSame('10.5', $totals->vatLines[1]->vat->amount);
        $this->assertSame('200', $totals->vatLines[2]->base->amount);
    }

    #[Test]
    public function an_invoice_without_lines_totals_zero(): void
    {
        $totals = InvoiceTotals::fromLines(Currency::EUR, []);

        $this->assertSame([], $totals->vatLines);
        $total = $totals->total();
        $this->assertSame('0', $total->amount);
        $this->assertSame(Currency::EUR, $total->currency);
    }

    #[Test]
    public function rejects_a_line_in_another_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceTotals::fromLines(
            Currency::CZK,
            [$this->line(
                '1',
                '10',
                '21',
                Currency::EUR,
            )],
        );
    }

    #[Test]
    public function rejects_a_rate_that_is_not_a_decimal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceTotals::fromLines(
            Currency::CZK,
            [$this->line(
                '1',
                '10',
                '-21',
            )],
        );
    }

    #[Test]
    public function a_non_payer_may_only_use_rate_zero(): void
    {
        $totals = InvoiceTotals::forSupplier(
            Currency::CZK,
            [$this->line(
                '2',
                '100',
                '0.00',
            )],
            false,
        );

        $this->assertSame('0', $totals->tax->amount);
        $total = $totals->total();
        $this->assertSame('200', $total->amount);
    }

    #[Test]
    public function refuses_a_non_zero_rate_of_a_non_payer(): void
    {
        $this->expectException(NonPayerVatRateException::class);

        // A zero price still states a rate the non-payer may not use.
        InvoiceTotals::forSupplier(
            Currency::CZK,
            [
                $this->line(
                    '1',
                    '100',
                    '0',
                ),
                $this->line(
                    '1',
                    '0',
                    '21',
                ),
            ],
            false,
        );
    }

    #[Test]
    public function a_payer_uses_any_rate(): void
    {
        $totals = InvoiceTotals::forSupplier(
            Currency::CZK,
            [$this->line(
                '1',
                '100',
                '21',
            )],
            true,
        );

        $this->assertSame('21', $totals->tax->amount);
    }

    /**
     * @return list<string>
     */
    private function rates(InvoiceTotals $totals): array
    {
        return array_map(
            static fn (VatLine $vatLine): string => $vatLine->rate,
            $totals->vatLines,
        );
    }

    private function line(
        string $quantity,
        string $price,
        string $taxRate,
        Currency $currency = Currency::CZK,
    ): InvoiceProductWriteData {
        return new InvoiceProductWriteData(
            name: 'Item',
            quantity: $quantity,
            unit: Unit::PIECES,
            price: Money::fromString($price, $currency),
            taxRate: $taxRate,
        );
    }
}
