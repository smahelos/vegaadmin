<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\SupplierDTO;
use App\Models\Supplier;

class EloquentSupplierMapper
{
    public static function toSupplierDTO(Supplier $model): SupplierDTO
    {
        return new SupplierDTO(
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
            supplier_logo: $model->getAttribute('supplier_logo'),
            account_number: $model->getAttribute('account_number') ? (int) $model->getAttribute('account_number') : null,
            bank_code: $model->getAttribute('bank_code') ?? null,
            bank_name: $model->getAttribute('bank_name'),
            iban: $model->getAttribute('iban'),
            swift: $model->getAttribute('swift'),
            user_id: $model->getAttribute('user_id'),
            is_default: (bool) $model->getAttribute('is_default'),
        );
    }
}
