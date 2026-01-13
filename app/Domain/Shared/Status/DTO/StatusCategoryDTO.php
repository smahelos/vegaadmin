<?php

namespace App\Domain\Shared\Status\DTO;

class StatusCategoryDTO
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
