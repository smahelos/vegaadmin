<?php

namespace App\Domain\Content\DTO;

class PageDTO
{
    public function __construct(
        public readonly int $id,
        /** @var array<string,string>|null */
        public readonly ?array $name,
        /** @var array<string,string>|null */
        public readonly ?array $slug,
        /** @var array<string,string>|null */
        public readonly ?array $description,
        public readonly bool $published,
        public readonly ?PageCategoryDTO $category = null,
    ) {}
}
