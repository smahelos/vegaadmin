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

    public function findByIdAny(int $id): ?Product
    {
        return Product::with([
            'category',
            'tax',
            'invoices',
        ])->find($id);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function findByIdForUserInt(int $id, int $userId): ?Product
    {
        $product = Product::where('id', $id)
            ->where('user_id', $userId)
            ->with([
                'category',
                'tax',
                'invoices',
            ])
            ->first();

        if (! $product) {
            throw new ModelNotFoundException('Product not found for user.');
        }

        return $product;
    }

    public function getUserProductCountInt(int $userId): int
    {
        return Product::where('user_id', $userId)->count();
    }

    public function updateById(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product->fresh();
    }

    public function deleteById(int $id): void
    {
        Product::where('id', $id)->delete();
    }
}
