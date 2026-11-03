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
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return TaxDTO::fromArray(
            [
                'id' => $model->getAttribute('id'),
                'name' => $model->getAttribute('name'),
                'slug' => $model->getAttribute('slug'),
                'rate' => (float) $model->getAttribute('rate'),
                'description' => $model->getAttribute('description'),
                'is_active' => (bool) $model->getAttribute('is_active'),
                'created_at' => $createdAt?->toDateTimeString(),
                'updated_at' => $updatedAt?->toDateTimeString(),
            ],
        );
    }
}
