<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\ClientDTO;
use App\Models\Client;

class EloquentClientMapper
{
    public static function toClientDTO(Client $model): ClientDTO
    {
        return new ClientDTO(
            id: (int) $model->id,
            name: $model->getAttribute('name'),
            street: $model->getAttribute('street'),
            city: $model->getAttribute('city'),
            zip: $model->getAttribute('zip'),
            country: $model->getAttribute('country'),
            email: $model->getAttribute('email'),
            phone: $model->getAttribute('phone'),
            dic: $model->getAttribute('dic'),
            ico: $model->getAttribute('ico'),
            description: $model->getAttribute('description'),
            created_at: $model->getAttribute('created_at')?->format('Y-m-d H:i:s'),
            user_id: $model->getAttribute('user_id'),
            is_default: (bool) $model->getAttribute('is_default'),
        );
    }
}
