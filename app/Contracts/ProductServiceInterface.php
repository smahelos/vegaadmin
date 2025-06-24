<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\User;

interface ProductServiceInterface
{
    /**
     * Create new product with business logic
     *
     * @param array $data
     * @param User $user
     * @return Product
     */
    public function createProduct(array $data, User $user): Product;

    /**
     * Update product with business logic
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function updateProduct(Product $product, array $data): Product;

    /**
     * Delete product with cleanup
     *
     * @param Product $product
     * @return bool
     */
    public function deleteProduct(Product $product): bool;

    /**
     * Get form data for product creation/editing
     *
     * @return array
     */
    public function getFormData(): array;

    /**
     * Generate slug from name
     *
     * @param string $name
     * @return string
     */
    public function generateSlug(string $name): string;

    /**
     * Invalidate product form data cache
     *
     * @return bool
     */
    public function invalidateFormDataCache(): bool;
}
