<?php

namespace App\Application\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use Illuminate\Support\Collection;
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
    public function updateProduct(int $productId, array $data, int $userId): ProductDTO;

    /**
     * Delete the given product.
     */
    public function deleteProduct(int $productId, int $userId): void;

    /**
     * List products with authorization.
     */
    public function listProducts(int $userId): Collection;

    /**
     * Get products for dropdown.
     */
    public function getProductsDropdown(int $userId): array;

    /**
     * Check if user can view product (without loading Eloquent model).
     */
    public function authorizeViewProduct(int $userId, int $productId): void;

    /**
     * Check if user can update product (without loading Eloquent model).
     */
    public function authorizeUpdateProduct(int $userId, int $productId): void;

    /**
     * Check if user can delete product (without loading Eloquent model).
     */
    public function authorizeDeleteProduct(int $userId, int $productId): void;

    /**
     * Handle product image upload and processing.
     */
    public function handleProductImage(\Illuminate\Http\UploadedFile|string|null $image, ?string $oldImage = null): ?string;

    /**
     * Generate slug from name
     */
    public function generateSlug(string $name): string;

    /**
     * Invalidate product form data cache
     */
    public function invalidateFormDataCache(): bool;

    /**
     * Get all product categories
     */
    public function getAllCategories(): array;

    /**
     * Get all suppliers for the current user (legacy)
     */
    public function getAllSuppliers(): array;

    /**
     * Get all suppliers for specific user (DTO version)
     */
    public function getAllSuppliersForUser(int $userId): array;

    /**
     * Delete cache for all categories
     */
    public function clearCategoriesCache(): void;

    /**
     * Find a product by ID for the given user.
     */
    public function findProduct(int $userId, int $id): ProductDTO;
}
