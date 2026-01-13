<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Mappers;

use App\Domain\Party\DTO\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Support\Collection;

class EloquentSupplierMapper
{
    public static function toSupplierDTO(Supplier $model): SupplierDTO
    {
        $invoices = [];
        if ($model->relationLoaded('invoices')) {
            foreach ($model->invoices as $invoice) {
                $invoices[] = self::toSupplierDTO($invoice);
            }
        }

        $toArray = static function ($value): ?array {
            if ($value === null) { return null; }
            return is_array($value) ? $value : [$value];
        };

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
            shortcut: $model->getAttribute('shortcut'),
            description: $model->getAttribute('description'),
            created_at: $model->getAttribute('created_at')?->format('Y-m-d H:i:s'),
            supplier_logo: $model->getAttribute('supplier_logo'),
            account_number: $model->getAttribute('account_number') ? (int) $model->getAttribute('account_number') : null,
            bank_code: $model->getAttribute('bank_code') ?? null,
            bank_name: $model->getAttribute('bank_name'),
            iban: $model->getAttribute('iban'),
            swift: $model->getAttribute('swift'),
            has_payment_info: (bool) $model->getAttribute('has_payment_info'),
            user_id: $model->getAttribute('user_id'),
            is_default: (bool) $model->getAttribute('is_default'),
            invoices: $invoices,
        );
    }
}
