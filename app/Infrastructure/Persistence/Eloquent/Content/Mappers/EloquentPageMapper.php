<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Mappers;

use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageCategoryDTO;
use App\Models\Page;
use App\Models\PageCategory;

class EloquentPageMapper
{
    public static function toPageDTO(Page $model): PageDTO
    {
        $children = [];
        if ($model->relationLoaded('children')) {
            foreach ($model->children as $child) {
                $children[] = self::toPageDTO($child);
            }
        }

        $category = null;
        if ($model->relationLoaded('category') && $model->category) {
            $category = self::toCategoryDTO($model->category);
        }

        $toArray = static function ($value): ?array {
            if ($value === null) { return null; }
            return is_array($value) ? $value : [$value];
        };

        return new PageDTO(
            id: (int) $model->id,
            name: $toArray($model->getAttribute('name')),
            slug: $toArray($model->getAttribute('slug')),
            description: $toArray($model->getAttribute('description')),
            content: $toArray($model->getAttribute('content')),
            categoryId: $model->getAttribute('category_id') ? (int) $model->getAttribute('category_id') : null,
            parentId: $model->getAttribute('parent_id') ? (int) $model->getAttribute('parent_id') : null,
            sortOrder: $model->getAttribute('sort_order') !== null ? (int) $model->getAttribute('sort_order') : null,
            published: (bool) $model->getAttribute('published'),
            children: $children,
            category: $category,
        );
    }

    public static function toCategoryDTO(PageCategory $model): PageCategoryDTO
    {
        $toArray = static function ($value): ?array {
            if ($value === null) { return null; }
            return is_array($value) ? $value : [$value];
        };

        $pages = [];
        if ($model->relationLoaded('pages')) {
            foreach ($model->pages as $page) {
                $pages[] = self::toPageDTO($page);
            }
        }

        return new PageCategoryDTO(
            id: (int) $model->id,
            name: $toArray($model->getAttribute('name')),
            slug: $toArray($model->getAttribute('slug')),
            description: $toArray($model->getAttribute('description')),
            pages: $pages,
        );
    }
}
