<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Mappers;

use App\Domain\Payment\DTO\PaymentMethodDTO;
use App\Models\PaymentMethod;

/**
 * Maps between Eloquent PaymentMethod models and PaymentMethod DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentPaymentMethodMapper
{
    /**
     * Convert Eloquent PaymentMethod model to PaymentMethodDTO.
     */
    public function toDto(PaymentMethod $model): PaymentMethodDTO
    {
        return PaymentMethodDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
            'description' => $model->getAttribute('description'),
            'is_active' => (bool) $model->getAttribute('is_active'),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert PaymentMethodDTO to array for Eloquent model creation/update.
     */
    public function fromDto(PaymentMethodDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'is_active' => $dto->is_active,
        ];
    }
}
