<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

/**
 * Domain DTO representing a printable invoice line item.
 * Holds Money VOs, without any presentation formatting.
 */
final readonly class PrintableInvoiceItemDTO
{
    /**
     * @param  string  $quantity  Decimal string, e.g. "1.500"
     * @param  string  $taxRate  VAT rate in percent as a decimal string, e.g. "21.00"
     */
    public function __construct(
        public string $name,
        public string $quantity,
        public Unit $unit,
        public Money $price,
        public string $taxRate,
        public Money $base,
    ) {}
}
