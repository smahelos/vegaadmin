<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

/**
 * One stored invoice line. A line without a product is a custom item typed into the invoice.
 */
final readonly class InvoiceProductDTO
{
    /**
     * @param  string  $quantity  Non-negative decimal string, e.g. "1.500"
     * @param  string  $taxRate  VAT rate in percent as a decimal string, e.g. "21.00"
     */
    public function __construct(
        public ?ProductId $productId,
        public string $name,
        public string $quantity,
        public Unit $unit,
        public Money $price,
        public string $taxRate,
    ) {}

    /** Price times quantity, rounded half up to cents, as the line prints it; VAT is computed by rate (InvoiceTotals). */
    public function base(): Money
    {
        return $this->price->multiplyBy($this->quantity);
    }
}
