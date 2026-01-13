<?php

namespace App\Application\Party\Contracts;

use App\Models\Supplier;

/**
 * Read-only operations for Supplier aggregate.
 */
interface SupplierReadRepositoryInterface
{
    /** @return array<int,string> */
    public function getSuppliersForDropdown(int $userId): array;

    public function getDefaultSupplier(int $userId): ?Supplier;

    public function findById(int $id): ?Supplier;

    /** Find by id without scoping to current user (admin/ownership checks handled at higher layer) */
    public function findByIdAny(int $id): ?Supplier;

    /** Find by id scoped to a specific user */
    public function findByIdForUser(int $id, int $userId): ?Supplier;
    
    public function allForAdmin(): array;
    
    public function allForUser(int $userId): array;
    
    public function firstForUser(int $userId): ?Supplier;
}
