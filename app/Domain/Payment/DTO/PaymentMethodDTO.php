<?php

namespace App\Domain\Payment\DTO;

class PaymentMethodDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly bool $is_active,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            is_active: (bool) ($data['is_active'] ?? false),
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
