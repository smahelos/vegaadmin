<?php

namespace App\Repositories;

use App\Contracts\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;

class SupplierRepository implements SupplierRepositoryInterface
{
    /**
     * Get suppliers for the current user as dropdown options
     * 
     * @return array
     */
    public function getSuppliersForDropdown(): array
    {
        return Supplier::where('user_id', Auth::id())
            ->pluck('name', 'id')
            ->toArray();
    }
    
    /**
     * Find default supplier for the current user
     * 
     * @return Supplier|null
     */
    public function getDefaultSupplier(): ?Supplier
    {
        return Supplier::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first() ?? Supplier::where('user_id', Auth::id())->first();
    }
    
    /**
     * Find supplier by ID for the current user
     * 
     * @param int $id
     * @return Supplier|null
     */
    public function findById(int $id): ?Supplier
    {
        return Supplier::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }

    /**
     * Create a new supplier from data
     * 
     * @param array $data
     * @return Supplier
     */
    public function create(array $data): Supplier
    {
        // Only set user_id from Auth if not already provided in data
        if (!isset($data['user_id']) || $data['user_id'] === null) {
            $data['user_id'] = Auth::id();
        }
        
        return Supplier::create($data);
    }
}
