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
    ) {}
}
