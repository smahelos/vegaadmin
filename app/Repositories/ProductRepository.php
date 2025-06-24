<?php

namespace App\Repositories;

use App\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Find all products for a specific user
     *
     * @param User $user
     * @return Collection
     */
    public function findUserProducts(User $user): Collection
    {
        return Product::where('user_id', $user->id)
            ->with(['category', 'tax'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user's product count
     *
     * @param User $user
     * @return int
     */
    public function getUserProductCount(User $user): int
    {
        return Product::where('user_id', $user->id)->count();
    }

    /**
     * Find product by ID for specific user
     *
     * @param int $id
     * @param User $user
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdForUser(int $id, User $user): Product
    {
        return Product::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    /**
     * Create new product
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update existing product
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product->fresh();
    }

    /**
     * Delete product
     *
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
