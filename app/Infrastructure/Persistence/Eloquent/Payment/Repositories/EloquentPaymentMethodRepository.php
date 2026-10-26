<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Models\PaymentMethod;
use Illuminate\Support\Collection;

/**
 * Eloquent-based repository for PaymentMethod entities.
 * Handles direct model operations.
 */
class EloquentPaymentMethodRepository
{
    public function all(): Collection
    {
        return PaymentMethod::all();
    }

    public function getAllForDropdown(): array
    {
        return PaymentMethod::pluck('slug', 'id')->toArray();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }
}
