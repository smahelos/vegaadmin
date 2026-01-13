<?php

namespace App\Domain\Content\DTO;


class PageCategoryDTO
{
    public function __construct(
        public readonly int $id,
        /** @var array<string,string>|null */
        public readonly ?array $name,
        /** @var array<string,string>|null */
        public readonly ?array $slug,
        /** @var array<string,string>|null */
        public readonly ?array $description,
        /** @var array<int, PageDTO> */
        public readonly array $pages = [],
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            pages: $data['pages'] ?? [],
        );
    }
}
