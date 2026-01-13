<?php

namespace App\Application\Product\Contracts;

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

    /**
     * Summary of getProductsForDropdown
     * @param int $userId
     * @return array
     */
    public function getProductsForDropdown(int $userId): array;

    /**
     * Get all products for admin
     * @return Collection
     */
    public function allForAdmin(): Collection;

    /**
     * Get all products for specific user
     * @return Collection
     */
    public function allForUser(int $userId): Collection;

    /**
     * Find product by ID for specific user.
     * @param int $id
     * @param int $userId
     * @return Product|null
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findByIdForUserInt(int $id, int $userId): ?Product;
}
