<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Console\Mappers;

use App\Domain\Shared\Console\DTO\ArtisanCommandDTO;
use App\Domain\Shared\Console\DTO\ArtisanCommandCategoryDTO;
use App\Models\ArtisanCommandCategory;
use App\Models\ArtisanCommand;

class EloquentArtisanCommandsMapper
{
    public static function toArtisanCommandDTO(ArtisanCommand $model): ArtisanCommandDTO
    {
        $sortOrder = $model->getAttribute('sort_order');

        return new ArtisanCommandDTO(
            id: (int) $model->id,
            name: $model->getAttribute('name'),

            description: $model->getAttribute('description'),
            signature: $model->getAttribute('signature'),
            category: $model->getAttribute('category'),
            created_at: $model->getAttribute('created_at'),
            is_active: $model->getAttribute('is_active'),
            command: $model->getAttribute('command'),
            parameters_description: $model->getAttribute('parameters_description'),
            sort_order: isset($sortOrder) ? (int) $sortOrder : null,
        );
    }

    public static function toArtisanCommandCategoryDTO(ArtisanCommandCategory $model): ArtisanCommandCategoryDTO
    {
        return new ArtisanCommandCategoryDTO(
            id: (int) $model->id,
            name: $model->getAttribute('name'),
            description: $model->getAttribute('description'),
            slug: $model->getAttribute('slug'),
            is_active: $model->getAttribute('is_active'),
        );
    }
}
