<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Models\Supplier;

class EloquentSupplierRepository
{
    public function findById(int $id): ?Supplier
    {
        return Supplier::find($id); // Caller must enforce ownership if needed
    }
}
