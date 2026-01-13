<?php

namespace App\Domain\Shared\Console\DTO;

class ArtisanCommandCategoryDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $slug,
        public readonly ?bool $is_active,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            slug: $data['slug'] ?? null,
            is_active: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }
}
