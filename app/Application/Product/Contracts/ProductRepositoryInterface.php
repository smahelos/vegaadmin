<?php

namespace App\Application\Product\Contracts;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find all products for a specific user
     */
    public function findUserProducts(User $user): Collection;

    /**
     * Create new product
     */
    public function create(array $data): Product;

    /**
     * Update existing product
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete product
     */
    public function delete(Product $product): bool;

    /**
     * Summary of getProductsForDropdown
     */
    public function getProductsForDropdown(int $userId): array;

    /**
     * Get all products for admin
     */
    public function allForAdmin(): Collection;

    /**
     * Get all products for specific user
     */
    public function allForUser(int $userId): Collection;

    /**
     * Find product by ID for specific user.
     *
     * @throws ModelNotFoundException
     */
    public function findByIdForUserInt(int $id, int $userId): ?Product;
}
