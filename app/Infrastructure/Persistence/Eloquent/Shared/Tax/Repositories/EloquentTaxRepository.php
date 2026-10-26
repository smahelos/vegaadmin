<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories;

use App\Models\Tax;

/**
 * Eloquent-based repository for Tax entities.
 * Handles direct model operations.
 */
class EloquentTaxRepository
{
    public function getAllTaxesForSelect(): array
    {
        return Tax::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Tax $tax) => [$tax->id => $tax->name])
            ->toArray();
    }

    public function getDphRatesForDropdown(): array
    {
        return Tax::where('slug', 'dph')->pluck('rate', 'id')->toArray();
    }

    public function create(array $data): Tax
    {
        return Tax::create($data);
    }
}
