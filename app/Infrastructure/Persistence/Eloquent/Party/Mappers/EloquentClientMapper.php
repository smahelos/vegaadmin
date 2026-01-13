<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\ClientDTO;
use App\Models\Client;
use Illuminate\Support\Collection;

class EloquentClientMapper
{
    public static function toClientDTO(Client $model): ClientDTO
    {
        $invoices = [];
        if ($model->relationLoaded('invoices')) {
            foreach ($model->invoices as $invoice) {
                $invoices[] = self::toClientDTO($invoice);
            }
        }

        $toArray = static function ($value): ?array {
            if ($value === null) { return null; }
            return is_array($value) ? $value : [$value];
        };

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
            shortcut: $model->getAttribute('shortcut'),
            description: $model->getAttribute('description'),
            created_at: $model->getAttribute('created_at')?->format('Y-m-d H:i:s'),
            user_id: $model->getAttribute('user_id'),
            is_default: (bool) $model->getAttribute('is_default'),
            invoices: $invoices,
        );
    }
}
