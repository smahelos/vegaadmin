<?php

namespace App\Contracts;

use App\Models\Supplier;

interface SupplierRepositoryInterface
{
    /**
     * Get suppliers for the current user as dropdown options
     * 
     * @return array
     */
    public function getSuppliersForDropdown(): array;
    
    /**
     * Find default supplier for the current user
     * 
     * @return Supplier|null
     */
    public function getDefaultSupplier(): ?Supplier;
    
    /**
     * Find supplier by ID for the current user
     * 
     * @param int $id
     * @return Supplier|null
     */
    public function findById(int $id): ?Supplier;
    
    /**
     * Create a new supplier from data
     * 
     * @param array $data
     * @return Supplier
     */
    public function create(array $data): Supplier;
}
