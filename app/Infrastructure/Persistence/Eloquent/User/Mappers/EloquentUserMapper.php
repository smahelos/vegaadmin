<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Mappers;

use App\Domain\User\DTO\UserDTO;
use App\Models\User;

/**
 * Maps between Eloquent User models and User DTOs.
 * Isolates Domain layer from Eloquent implementation details.
 */
class EloquentUserMapper
{
    /**
     * Convert Eloquent User model to UserDTO.
     */
    public function toDto(User $model): UserDTO
    {
        $emailVerifiedAt = $model->getAttribute('email_verified_at');
        $createdAt = $model->getAttribute('created_at');
        $updatedAt = $model->getAttribute('updated_at');

        return UserDTO::fromArray(
            [
                'id' => $model->getAttribute('id'),
                'name' => $model->getAttribute('name'),
                'email' => $model->getAttribute('email'),
                'email_verified_at' => $emailVerifiedAt?->toDateTimeString(),
                'created_at' => $createdAt?->toDateTimeString(),
                'updated_at' => $updatedAt?->toDateTimeString(),
            ],
        );
    }
}
