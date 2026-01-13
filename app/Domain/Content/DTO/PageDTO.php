<?php

namespace App\Domain\Content\DTO;

class   PageDTO
{
    public function __construct(
        public readonly int $id,
        /** @var array<string,string>|null */
        public readonly ?array $name,
        /** @var array<string,string>|null */
        public readonly ?array $slug,
        /** @var array<string,string>|null */
        public readonly ?array $description,
        /** @var array<string,string>|null */
        public readonly ?array $content,
        public readonly ?int $categoryId,
        public readonly ?int $parentId,
        public readonly ?int $sortOrder,
        public readonly bool $published,
        /** @var PageDTO[] */
        public readonly array $children = [],
        public readonly ?PageCategoryDTO $category = null,
    ) {}

    /** Array-based constructor for mappers. */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            content: $data['content'] ?? null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
            sortOrder: isset($data['sortOrder']) ? (int) $data['sortOrder'] : null,
            published: (bool) ($data['published'] ?? false),
            children: $data['children'] ?? [],
            category: $data['category'] ?? null,
        );
    }
}
