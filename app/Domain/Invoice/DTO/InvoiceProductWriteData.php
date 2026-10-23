<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;

/**
 * One invoice line. A line without a product is a custom item typed into the invoice.
 */
final readonly class InvoiceProductWriteData
{
    /**
     * @param  string  $quantity  Non-negative decimal string, e.g. "1.5"
     * @param  string  $taxRate  VAT rate in percent as a decimal string, e.g. "21"
     */
    public function __construct(
        public string $name,
        public string $quantity,
        public Unit $unit,
        public Money $price,
        public string $taxRate,
        public ?ProductId $productId = null,
        public ?string $category = null,
        public ?string $description = null,
    ) {}

    public function isCustomProduct(): bool
    {
        return $this->productId === null;
    }

    /** Price times quantity, rounded half up to cents. */
    public function base(): Money
    {
        return $this->price->multiplyBy($this->quantity);
    }

    /** VAT of the line, computed from the rounded base and rounded half up to cents. */
    public function taxAmount(): Money
    {
        return $this->base()->multiplyBy($this->taxRate, 8)->divideRounding(
            100,
            2,
            Money::ROUND_HALF_UP,
        );
    }

    public function total(): Money
    {
        return $this->base()->add($this->taxAmount());
    }
}
