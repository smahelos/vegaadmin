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
        $query = Tax::query();
        $query->orderBy('name');
        $taxes = $query->get();
        $taxes = $taxes->mapWithKeys(fn (Tax $tax) => [$tax->id => $tax->name]);

        return $taxes->toArray();
    }

    public function getDphRatesForDropdown(): array
    {
        $query = Tax::where('slug', 'dph');
        $rates = $query->pluck('rate', 'id');

        return $rates->toArray();
    }

    public function create(array $data): Tax
    {
        return Tax::create($data);
    }
}
