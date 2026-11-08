<?php

namespace App\Application\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Models\Product;
use App\Models\User;

interface ProductApplicationServiceInterface
{
    /**
     * Get form data needed for product create/edit screens.
     */
    public function getFormData(): array;

    /**
     * Create a new product for the given user.
     */
    public function createProduct(array $data, int $userId): ProductDTO;

    /**
     * Update product.
     */
    public function updateProduct(
        int $productId,
        array $data,
        int $userId,
    ): ProductDTO;

    /**
     * Delete the given product.
     */
    public function deleteProduct(int $productId, int $userId): void;

    /**
     * Check if user can create a product.
     */
    public function authorizeCreateProduct(int $userId): void;

    /**
     * Return the product the user can view.
     */
    public function authorizeViewProduct(int $userId, int $productId): ProductDTO;

    /**
     * Return the product the user can update.
     */
    public function authorizeUpdateProduct(int $userId, int $productId): ProductDTO;

    /**
     * Check if user can delete product.
     */
    public function authorizeDeleteProduct(int $userId, int $productId): void;

    /**
     * Generate slug from name
     */
    public function generateSlug(string $name): string;

    /**
     * Get all product categories
     */
    public function getAllCategories(): array;
}
