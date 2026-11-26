<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * The tax base and VAT of one VAT rate of an invoice, as the summary by rate prints them.
 */
final readonly class VatLine
{
    /**
     * @param  string  $rate  VAT rate in percent without trailing zeros, e.g. "21" or "10.5"
     */
    public function __construct(
        public string $rate,
        public Money $base,
        public Money $vat,
    ) {}

    public function total(): Money
    {
        return $this->base->add($this->vat);
    }
}
