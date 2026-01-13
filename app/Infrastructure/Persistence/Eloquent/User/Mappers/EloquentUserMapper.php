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
            'password' => $model->getAttribute('password'),
            'email_verified_at' => $model->getAttribute('email_verified_at')?->toDateTimeString(),
            'created_at' => $model->getAttribute('created_at')?->toDateTimeString(),
            'updated_at' => $model->getAttribute('updated_at')?->toDateTimeString(),
        ]);
    }

    /**
     * Convert UserDTO to array for Eloquent model creation/update.
     */
    public function fromDto(UserDTO $dto): array
    {
        return [
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
            'email_verified_at' => $dto->email_verified_at ? \Carbon\Carbon::parse($dto->email_verified_at) : null,
            'created_at' => $dto->created_at ? \Carbon\Carbon::parse($dto->created_at) : null,
            'updated_at' => $dto->updated_at ? \Carbon\Carbon::parse($dto->updated_at) : null,
        ];
    }
}
