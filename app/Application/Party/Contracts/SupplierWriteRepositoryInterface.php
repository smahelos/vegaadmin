<?php

namespace App\Application\Party\Contracts;

use App\Models\Supplier;

/**
 * Write operations for Supplier aggregate.
 */
interface SupplierWriteRepositoryInterface
{
    public function create(array $data): Supplier;

    public function unsetOthersDefault(int $currentId, int $userId): void;
    
    public function findByIdForUser(int $id, int $userId): ?Supplier;

    /** Update supplier by ID with provided attributes */
    public function updateById(int $id, array $data): bool;

    /** Delete supplier by ID */
    public function deleteById(int $id): bool;
}
