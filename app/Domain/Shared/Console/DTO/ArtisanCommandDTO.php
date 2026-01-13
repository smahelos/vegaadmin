<?php

namespace App\Domain\Shared\Console\DTO;

class ArtisanCommandDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?string $signature,
        public readonly ?string $category,
        public readonly ?string $created_at,
        public readonly ?string $is_active,
        public readonly ?string $command,
        public readonly ?string $parameters_description,
        public readonly ?int $sort_order,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            signature: $data['signature'] ?? null,
            category: $data['category'] ?? null,
            created_at: $data['created_at'] ?? null,
            is_active: $data['is_active'] ?? null,
            command: $data['command'] ?? null,
            parameters_description: $data['parameters_description'] ?? null,
            sort_order: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
        );
    }
}
