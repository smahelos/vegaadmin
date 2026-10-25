<?php

namespace App\Infrastructure\Persistence\Eloquent\Content\Mappers;

use App\Domain\Content\DTO\PageCategoryDTO;
use App\Domain\Content\DTO\PageDTO;
use App\Models\Page;
use App\Models\PageCategory;

class EloquentPageMapper
{
    public static function toPageDTO(Page $model): PageDTO
    {
        $category = null;
        if ($model->relationLoaded('category') && $model->category) {
            $category = self::toCategoryDTO($model->category);
        }

        $toArray = static function ($value): ?array {
            if ($value === null) {
                return null;
            }

            return is_array($value) ? $value : [$value];
        };

        return new PageDTO(
            id: (int) $model->id,
            name: $toArray($model->getAttribute('name')),
            slug: $toArray($model->getAttribute('slug')),
            description: $toArray($model->getAttribute('description')),
            published: (bool) $model->getAttribute('published'),
            category: $category,
        );
    }

    public static function toCategoryDTO(PageCategory $model): PageCategoryDTO
    {
        $toArray = static function ($value): ?array {
            if ($value === null) {
                return null;
            }

            return is_array($value) ? $value : [$value];
        };

        return new PageCategoryDTO(
            id: (int) $model->id,
            name: $toArray($model->getAttribute('name')),
            slug: $toArray($model->getAttribute('slug')),
            description: $toArray($model->getAttribute('description')),
        );
    }
}
