<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Tax\Repositories;

use App\Models\Tax;
use Illuminate\Support\Collection;

/**
 * Eloquent-based repository for Tax entities.
 * Handles direct model operations.
 */
class EloquentTaxRepository
{
    public function getAllTaxes(): array
    {
        return Tax::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Tax $tax) => [$tax->slug => $tax->name])
            ->toArray();
    }

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

    public function findById(int $id): ?Tax
    {
        return Tax::find($id);
    }

    public function findBySlug(string $slug): ?Tax
    {
        return Tax::where('slug', $slug)->first();
    }

    public function create(array $data): Tax
    {
        return Tax::create($data);
    }

    public function updateById(int $id, array $data): Tax
    {
        $tax = Tax::findOrFail($id);
        $tax->update($data);
        return $tax->fresh();
    }

    public function deleteById(int $id): bool
    {
        return Tax::destroy($id) > 0;
    }
}
