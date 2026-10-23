<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

/**
 * Domain DTO representing a printable invoice line item.
 * Holds Money VOs, without any presentation formatting.
 */
class PrintableInvoiceItemData
{
    public function __construct(
        public readonly string $name,
        public readonly float $quantity,
        public readonly Unit $unit,
        public readonly Money $price,
        public readonly float $tax_rate,
        public readonly Money $tax_amount,
        public readonly Money $line_total,
    ) {}
}
