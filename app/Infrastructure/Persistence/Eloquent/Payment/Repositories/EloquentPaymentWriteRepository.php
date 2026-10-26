<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\PaymentWriteRepositoryInterface;
use App\Models\Payment;

class EloquentPaymentWriteRepository implements PaymentWriteRepositoryInterface
{
    public function create(array $attributes): Payment
    {
        return Payment::create($attributes);
    }

    public function updateById(int $id, array $data): ?Payment
    {
        $model = Payment::query()->find($id);
        if (! $model) {
            return null;
        }
        if (! empty($data)) {
            $model->fill($data);
            $model->save();
        }

        return $model;
    }
}
