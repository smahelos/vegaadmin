<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Unit\Enums\Unit;

/**
 * Immutable write data for Invoice product rows.
 */
class InvoiceProductWriteData
{
    public function __construct(
        public readonly ?int $product_id = null,
        public readonly string $name = '',
        public readonly float $quantity = 1.0,
        public readonly float $price = 0.0,
        public readonly string $currency = 'CZK',
        public readonly Unit $unit = Unit::PIECES,
        public readonly ?string $category = null,
        public readonly ?string $description = null,
        public readonly float $tax_rate = 0.0,
        public readonly float $tax_amount = 0.0,
        public readonly float $total_price = 0.0,
        public readonly bool $is_custom_product = false,
    ) {}

    /**
     * Build a product row from raw form data. A row without product_id (or flagged custom) is a
     * custom product; tax_amount and total_price are always derived from quantity, price and tax rate.
     */
    public static function fromArray(array $data): self
    {
        $isCustom = empty($data['product_id']) || !empty($data['is_custom_product']);
        $quantity = (float)($data['quantity'] ?? 1);
        $price = (float)($data['price'] ?? 0);
        $taxRate = (float)($data['tax_rate'] ?? 21);

        return new self(
            product_id: $isCustom ? null : (int)$data['product_id'],
            name: (string)($data['name'] ?? ''),
            quantity: $quantity,
            price: $price,
            currency: (string)($data['currency'] ?? 'CZK'),
            unit: Unit::fromInput($data['unit'] ?? null),
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            tax_rate: $taxRate,
            tax_amount: ($quantity * $price * $taxRate) / 100,
            total_price: $quantity * $price * (1 + $taxRate / 100),
            is_custom_product: $isCustom,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->product_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'currency' => $this->currency,
            'unit' => $this->unit->value,
            'category' => $this->category,
            'description' => $this->description,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_price' => $this->total_price,
            'is_custom_product' => $this->is_custom_product,
        ];
    }
}
