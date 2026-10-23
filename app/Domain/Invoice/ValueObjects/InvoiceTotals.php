<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Subtotal, VAT and total of an invoice, summed from its lines. The lines are the source of truth;
 * each line is rounded on its own, so the totals equal the sums of the printed line amounts.
 */
final readonly class InvoiceTotals
{
    private function __construct(
        public Money $subtotal,
        public Money $tax,
    ) {}

    public static function zero(Currency $currency): self
    {
        $zero = Money::fromString('0', $currency->value);

        return new self($zero, $zero);
    }

    /**
     * @param  list<InvoiceProductWriteData>  $products
     *
     * @throws \InvalidArgumentException when a line is in another currency than the invoice
     */
    public static function fromProducts(Currency $currency, array $products): self
    {
        $totals = self::zero($currency);
        foreach ($products as $product) {
            $totals = $totals->withLine($product->base(), $product->taxAmount());
        }

        return $totals;
    }

    /**
     * @throws \InvalidArgumentException when the line is in another currency than the invoice
     */
    public function withLine(Money $base, Money $tax): self
    {
        return new self($this->subtotal->add($base), $this->tax->add($tax));
    }

    public function total(): Money
    {
        return $this->subtotal->add($this->tax);
    }
}
