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
        return UserDTO::fromArray([
            'id' => $model->getAttribute('id'),
            'name' => $model->getAttribute('name'),
            'email' => $model->getAttribute('email'),
            'email_verified_at' => $model->getAttribute('email_verified_at')?->toDateTimeString(),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }
}
