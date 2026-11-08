<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Application\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function make(array $attributes = []): Product
    {
        return new Product($attributes);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findByIdForUserInt(int $id, int $userId): ?Product
    {
        $query = Product::where('id', $id);
        $query->where('user_id', $userId);
        $query->with([
            'category',
            'tax',
            'invoices',
        ]);
        $product = $query->first();

        if (!$product) {
            throw new ModelNotFoundException('Product not found for user.');
        }

        return $product;
    }

    public function getUserProductCountInt(int $userId): int
    {
        $query = Product::where('user_id', $userId);

        return $query->count();
    }

    public function updateById(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product->fresh();
    }

    public function deleteById(int $id): void
    {
        $query = Product::where('id', $id);
        $query->delete();
    }
}
