<?php

namespace App\Domain\Invoice\DTO;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Read DTO for Invoice line item (invoice_products).
 * Keeps monetary values as Money VOs to avoid float issues.
 */
class InvoiceProductDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $invoice_id,
        public readonly ?int $product_id,
        public readonly string $name,
        public readonly float $quantity,
        public readonly ?string $unit,
        public readonly ?string $category,
        public readonly ?string $description,
        public readonly Money $price,
        public readonly string $currency,
        public readonly float $tax_rate,
        public readonly Money $tax_amount,
        public readonly Money $total_price,
        public readonly bool $is_custom_product,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
    ) {}

    /**
     * Array-based constructor for mappers.
     * Accepts Money instances directly or amount+currency pairs.
     */
    public static function fromArray(array $data): self
    {
        $currency = strtoupper((string)($data['currency'] ?? 'CZK'));

        $price = isset($data['price']) && $data['price'] instanceof Money
            ? $data['price']
            : Money::fromString(
                is_string($data['price'] ?? null) ? (string)$data['price'] : number_format((float)($data['price'] ?? 0), 2, '.', ''),
                $currency
            );

        $taxAmount = isset($data['tax_amount']) && $data['tax_amount'] instanceof Money
            ? $data['tax_amount']
            : Money::fromString(
                is_string($data['tax_amount'] ?? null) ? (string)$data['tax_amount'] : number_format((float)($data['tax_amount'] ?? 0), 2, '.', ''),
                $currency
            );

        $totalPrice = isset($data['total_price']) && $data['total_price'] instanceof Money
            ? $data['total_price']
            : Money::fromString(
                is_string($data['total_price'] ?? null) ? (string)$data['total_price'] : number_format((float)($data['total_price'] ?? 0), 2, '.', ''),
                $currency
            );

        return new self(
            id: (int) $data['id'],
            invoice_id: (int) $data['invoice_id'],
            product_id: isset($data['product_id']) ? (int)$data['product_id'] : null,
            name: (string) ($data['name'] ?? ''),
            quantity: isset($data['quantity']) ? (float)$data['quantity'] : 1.0,
            unit: $data['unit'] ?? null,
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            price: $price,
            currency: $currency,
            tax_rate: isset($data['tax_rate']) ? (float)$data['tax_rate'] : 0.0,
            tax_amount: $taxAmount,
            total_price: $totalPrice,
            is_custom_product: (bool)($data['is_custom_product'] ?? false),
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }
}
