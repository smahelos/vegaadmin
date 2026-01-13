<?php

namespace App\Domain\Product\DTO;

class ProductDTO
{
    /**
     * Domain Product read DTO. Relations are represented as lightweight arrays to avoid Eloquent leakage.
     * - tax: ['id'=>int, 'name'=>string, 'rate'=>float]|null
     * - category: ['id'=>int, 'name'=>string]|null
     * - supplier: ['id'=>int, 'name'=>string]|null
     * - invoices: int[] of invoice IDs (or empty array)
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $slug,
        public readonly ?string $image,
    public readonly ?\App\Domain\Shared\Money\ValueObjects\Money $price,
        public readonly ?string $unit,
        public readonly ?int $category_id,
        /** @var array{id:int,name:string}|null */
        public readonly ?array $category,
        public readonly ?int $supplier_id,
        /** @var array{id:int,name:string}|null */
        public readonly ?array $supplier,
        public readonly ?string $currency,
        public readonly ?int $tax_id,
        /** @var array{id:int,name:string,rate:float}|null */
        public readonly ?array $tax,
        /** @var int[] */
        public readonly array $invoices,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        public readonly ?int $user_id,
        public readonly bool $is_default,
        public readonly bool $is_active,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            slug: $data['slug'] ?? null,
            image: $data['image'] ?? null,
            price: isset($data['price'])
                ? ($data['price'] instanceof \App\Domain\Shared\Money\ValueObjects\Money
                    ? $data['price']
                    : (isset($data['currency'])
                        ? \App\Domain\Shared\Money\ValueObjects\Money::fromFloat(
                            (float)$data['price'],
                            strtoupper((string)$data['currency'])
                        )
                        : null))
                : null,
            unit: $data['unit'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            category: isset($data['category']) && is_array($data['category']) ? $data['category'] : null,
            supplier_id: isset($data['supplier_id']) ? (int) $data['supplier_id'] : null,
            supplier: isset($data['supplier']) && is_array($data['supplier']) ? $data['supplier'] : null,
            currency: $data['currency'] ?? null,
            tax_id: isset($data['tax_id']) ? (int) $data['tax_id'] : null,
            tax: isset($data['tax']) && is_array($data['tax']) ? $data['tax'] : null,
            invoices: array_values(array_filter($data['invoices'] ?? [], fn($v) => is_int($v) || ctype_digit((string)$v))),
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            user_id: isset($data['user_id']) ? (int) $data['user_id'] : null,
            is_default: (bool) ($data['is_default'] ?? false),
            is_active: (bool) ($data['is_active'] ?? false),
        );
    }
}
