<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Mappers;

use App\Domain\Shared\Status\DTO\StatusCategoryDTO;
use App\Models\StatusCategory;

/**
 * Maps between Eloquent StatusCategory models and StatusCategory DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentStatusCategoryMapper
{
    /**
     * Convert Eloquent StatusCategory model to StatusCategoryDTO.
     */
    public function toDto(StatusCategory $model): StatusCategoryDTO
    {
        return StatusCategoryDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
            'description' => $model->getAttribute('description'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert TaxDTO to array for Eloquent model creation/update.
     */
    public function fromDto(StatusCategoryDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
        ];
    }
}
