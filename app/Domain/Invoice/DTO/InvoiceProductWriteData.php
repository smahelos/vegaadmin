<?php

namespace App\Domain\Invoice\DTO;

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
        public readonly ?string $unit = null,
        public readonly ?string $category = null,
        public readonly ?string $description = null,
        public readonly float $tax_rate = 0.0,
        public readonly float $tax_amount = 0.0,
        public readonly float $total_price = 0.0,
        public readonly bool $is_custom_product = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            product_id: isset($data['product_id']) ? (int)$data['product_id'] : null,
            name: (string)($data['name'] ?? ''),
            quantity: isset($data['quantity']) ? (float)$data['quantity'] : 1.0,
            price: isset($data['price']) ? (float)$data['price'] : 0.0,
            currency: (string)($data['currency'] ?? 'CZK'),
            unit: $data['unit'] ?? null,
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            tax_rate: isset($data['tax_rate']) ? (float)$data['tax_rate'] : 0.0,
            tax_amount: isset($data['tax_amount']) ? (float)$data['tax_amount'] : 0.0,
            total_price: isset($data['total_price']) ? (float)$data['total_price'] : 0.0,
            is_custom_product: (bool)($data['is_custom_product'] ?? false),
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
            'unit' => $this->unit,
            'category' => $this->category,
            'description' => $this->description,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_price' => $this->total_price,
            'is_custom_product' => $this->is_custom_product,
        ];
    }
}
