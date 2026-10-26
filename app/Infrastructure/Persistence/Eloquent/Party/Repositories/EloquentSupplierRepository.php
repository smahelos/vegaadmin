<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Application\Party\Contracts\SupplierReadRepositoryInterface;
use App\Application\Party\Contracts\SupplierWriteRepositoryInterface;
use App\Models\Supplier;

class EloquentSupplierRepository implements SupplierReadRepositoryInterface, SupplierWriteRepositoryInterface
{
    public function getSuppliersForDropdown(int $userId): array
    {
        return Supplier::where('user_id', $userId)->pluck('name', 'id')->toArray();
    }

    public function getDefaultSupplier(int $userId): ?Supplier
    {
        $default = Supplier::where('user_id', $userId)->where('is_default', true)->first();

        // Without a default the most recently created supplier stands in
        return $default ?? Supplier::where('user_id', $userId)->latest()->latest('id')->first();
    }

    public function findById(int $id): ?Supplier
    {
        return Supplier::find($id); // Caller must enforce ownership if needed
    }

    public function findByIdAny(int $id): ?Supplier
    {
        return Supplier::find($id);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function allForAdmin(): array
    {
        return Supplier::all()->toArray();
    }

    public function allForUser(int $userId): array
    {
        return Supplier::where('user_id', $userId)->get()->toArray();
    }

    public function firstForUser(int $userId): ?Supplier
    {
        return Supplier::where('user_id', $userId)->orderByDesc('created_at')->first();
    }

    public function findByIdForUser(int $id, int $userId): ?Supplier
    {
        return Supplier::where('id', $id)->where('user_id', $userId)->first();
    }
}
