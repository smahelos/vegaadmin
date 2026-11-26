<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use JsonSerializable;

/**
 * A line of the edit form's item editor. The JSON keys are the request keys of a line in InvoiceRequest,
 * which the editor sends back unchanged.
 */
final readonly class EditableInvoiceItemDTO implements JsonSerializable
{
    public function __construct(
        public ?ProductId $productId,
        public string $name,
        public string $quantity,
        public Unit $unit,
        public Money $price,
        public string $taxRate,
    ) {}

    /** @return array{product_id: ?int, name: string, quantity: string, unit: string, price: string, currency: string, tax_rate: string} */
    public function jsonSerialize(): array
    {
        return [
            'product_id' => $this->productId?->value,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit->value,
            'price' => $this->price->amount,
            'currency' => $this->price->currency->value,
            'tax_rate' => $this->taxRate,
        ];
    }
}
