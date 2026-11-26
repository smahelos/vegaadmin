<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\NonPayerVatRateException;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use InvalidArgumentException;

/**
 * Subtotal, VAT and total of an invoice, summed by VAT rate from its lines. For each rate the base is
 * the unrounded sum of price times quantity; the base and the VAT of that sum are rounded once per rate,
 * and the totals are the sums of the rounded rates, so they equal the printed summary by rate.
 */
final readonly class InvoiceTotals
{
    /**
     * @param  list<VatLine>  $vatLines  From the highest rate
     */
    private function __construct(
        public array $vatLines,
        public Money $subtotal,
        public Money $tax,
    ) {}

    /**
     * @param  list<InvoiceProductWriteData|InvoiceProductDTO>  $lines
     *
     * @throws InvalidArgumentException when a line is in another currency than the invoice or a rate is not a decimal
     */
    public static function fromLines(Currency $currency, array $lines): self
    {
        $zero = Money::fromString('0', $currency);
        $unroundedBases = [];
        foreach ($lines as $line) {
            $rate = self::normalizedRate($line->taxRate);
            $lineBase = $line->price->multiplyBy($line->quantity, 8);
            $rateBase = $unroundedBases[$rate] ?? $zero;
            $unroundedBases[$rate] = $rateBase->add($lineBase);
        }
        krsort($unroundedBases, SORT_NUMERIC);

        $vatLines = [];
        $subtotal = $zero;
        $tax = $zero;
        foreach ($unroundedBases as $rate => $unroundedBase) {
            $rate = (string) $rate;
            $base = $unroundedBase->divideRounding(
                1,
                2,
                Money::ROUND_HALF_UP,
            );
            $unroundedVat = $unroundedBase->multiplyBy($rate, 8);
            $vat = $unroundedVat->divideRounding(
                100,
                2,
                Money::ROUND_HALF_UP,
            );
            $vatLines[] = new VatLine(
                $rate,
                $base,
                $vat,
            );
            $subtotal = $subtotal->add($base);
            $tax = $tax->add($vat);
        }

        return new self(
            $vatLines,
            $subtotal,
            $tax,
        );
    }

    /**
     * Totals of the lines a supplier writes: a supplier that is not a VAT payer may only use rate 0.
     *
     * @param  list<InvoiceProductWriteData>  $lines
     *
     * @throws NonPayerVatRateException when a non-payer's line has a non-zero rate
     * @throws InvalidArgumentException when a line is in another currency than the invoice or a rate is not a decimal
     */
    public static function forSupplier(
        Currency $currency,
        array $lines,
        bool $vatPayer,
    ): self {
        $totals = self::fromLines($currency, $lines);
        if ($vatPayer) {
            return $totals;
        }
        foreach ($totals->vatLines as $vatLine) {
            if ($vatLine->rate !== '0') {
                throw new NonPayerVatRateException(
                    'A supplier that is not a VAT payer used a non-zero VAT rate',
                );
            }
        }

        return $totals;
    }

    public function total(): Money
    {
        return $this->subtotal->add($this->tax);
    }

    /**
     * "21.00" and "21" are one rate.
     *
     * @throws InvalidArgumentException when the rate is not a non-negative decimal
     */
    private static function normalizedRate(string $rate): string
    {
        if (!preg_match('/^\d+(\.\d+)?$/', $rate)) {
            throw new InvalidArgumentException('Invalid VAT rate: ' . $rate);
        }
        $rate = ltrim($rate, '0');
        if (str_contains($rate, '.')) {
            $rate = rtrim($rate, '0');
            $rate = rtrim($rate, '.');
        }
        if ($rate === '' || $rate[0] === '.') {
            return '0' . $rate;
        }

        return $rate;
    }
}
