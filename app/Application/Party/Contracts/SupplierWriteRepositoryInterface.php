<?php

namespace App\Application\Party\Contracts;

use App\Models\Supplier;

/**
 * Write operations for Supplier aggregate.
 */
interface SupplierWriteRepositoryInterface
{
    public function create(array $data): Supplier;
}
