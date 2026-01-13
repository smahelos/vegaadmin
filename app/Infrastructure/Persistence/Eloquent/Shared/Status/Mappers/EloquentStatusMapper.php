<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Mappers;

use App\Domain\Shared\Status\DTO\StatusDTO;
use App\Models\Status;

/**
 * Maps between Eloquent Status models and Status DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentStatusMapper
{
    /**
     * Convert Eloquent Status model to StatusDTO.
     */
    public function toDto(Status $model): StatusDTO
    {
        return StatusDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
            'description' => $model->getAttribute('description'),
            'color' => $model->getAttribute('color'),
            'category_id' => (int) $model->getAttribute('category_id'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert TaxDTO to array for Eloquent model creation/update.
     */
    public function fromDto(StatusDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'color' => $dto->color,
            'category_id' => $dto->category_id, 
            'is_active' => $dto->is_active,
        ];
    }
}
