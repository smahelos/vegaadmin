<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Tax\Mappers;

use App\Domain\Shared\Tax\DTO\TaxDTO;
use App\Models\Tax;

/**
 * Maps between Eloquent Tax models and Tax DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentTaxMapper
{
    /**
     * Convert Eloquent Tax model to TaxDTO.
     */
    public function toDto(Tax $model): TaxDTO
    {
        return TaxDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
            'rate' => (float) $model->getAttribute('rate'),
            'description' => $model->getAttribute('description'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert TaxDTO to array for Eloquent model creation/update.
     */
    public function fromDto(TaxDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'rate' => $dto->rate,
            'description' => $dto->description,
            'is_active' => $dto->is_active,
        ];
    }
}
