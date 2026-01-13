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
        return PaymentMethod::all()->pluck('slug', 'id')->toArray();
    }

    public function findById(int $id): ?PaymentMethod
    {
        return PaymentMethod::find($id);
    }

    public function findBySlug(string $slug): ?PaymentMethod
    {
        return PaymentMethod::where('slug', $slug)->first();
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::create($data);
    }

    public function updateById(int $id, array $data): PaymentMethod
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        $paymentMethod->update($data);
        return $paymentMethod->fresh();
    }

    public function deleteById(int $id): bool
    {
        return PaymentMethod::destroy($id) > 0;
    }
}
