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
    public function __construct(
        public string $name,
        public float $quantity,
        public Unit $unit,
        public Money $price,
        public float $taxRate,
        public Money $lineTotal,
    ) {}
}
