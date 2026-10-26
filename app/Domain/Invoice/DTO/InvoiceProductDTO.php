<?php

namespace App\Domain\Invoice\DTO;

/**
 * Read DTO for Invoice line item (invoice_products).
 */
class InvoiceProductDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $category,
        public readonly ?string $description,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
    ) {}

    /**
     * Array-based constructor for mappers.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: (string) ($data['name'] ?? ''),
            category: $data['category'] ?? null,
            description: $data['description'] ?? null,
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }
}
