<?php

namespace App\Domain\Content\DTO;

/**
 * Immutable write data for Page mutations.
 * All fields are optional to support partial updates; null means "do not change".
 */
class PageWriteData
{
    /**
     * @param array<string,string>|null $name
     * @param array<string,string>|null $slug
     * @param array<string,string>|null $description
     * @param array<string,string>|null $content
     */
    public function __construct(
        public readonly ?array $name = null,
        public readonly ?array $slug = null,
        public readonly ?array $description = null,
        public readonly ?array $content = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $parentId = null,
        public readonly ?int $sortOrder = null,
        public readonly ?bool $published = null,
    ) {}

    public function toModelAttributes(): array
    {
        $map = [];
        if ($this->name !== null) { $map['name'] = $this->name; }
        if ($this->slug !== null) { $map['slug'] = $this->slug; }
        if ($this->description !== null) { $map['description'] = $this->description; }
        if ($this->content !== null) { $map['content'] = $this->content; }
        if ($this->categoryId !== null) { $map['category_id'] = $this->categoryId; }
        if ($this->parentId !== null) { $map['parent_id'] = $this->parentId; }
        if ($this->sortOrder !== null) { $map['sort_order'] = $this->sortOrder; }
        if ($this->published !== null) { $map['published'] = $this->published; }
        return $map;
    }
}
