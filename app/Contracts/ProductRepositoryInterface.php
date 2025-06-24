<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find all products for a specific user
     *
     * @param User $user
     * @return Collection
     */
    public function findUserProducts(User $user): Collection;

    /**
     * Get user's product count
     *
     * @param User $user
     * @return int
     */
    public function getUserProductCount(User $user): int;

    /**
     * Find product by ID for specific user
     *
     * @param int $id
     * @param User $user
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdForUser(int $id, User $user): Product;

    /**
     * Create new product
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update existing product
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete product
     *
     * @param Product $product
     * @return bool
     */
    public function delete(Product $product): bool;
}
