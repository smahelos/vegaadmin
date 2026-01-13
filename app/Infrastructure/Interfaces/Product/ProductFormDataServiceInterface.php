<?php

namespace App\Infrastructure\Interfaces\Product;

/**
 * Interface for Product-specific form data operations.
 * Handles categories, suppliers, tax rates for Product forms.
 */
interface ProductFormDataServiceInterface
{
    /**
     * Get form data for product creation/editing
     */
    public function getFormData(): array;

    /**
     * Get all product categories
     */
    public function getAllCategories(): array;

    /**
     * Get all suppliers for the current user
     */
    public function getAllSuppliers(): array;

    /**
     * Get all suppliers for specific user
     */
    public function getAllSuppliersForUser(int $userId): array;

    /**
     * Invalidate product form data cache
     */
    public function invalidateFormDataCache(): bool;

    /**
     * Delete cache for all categories
     */
    public function clearCategoriesCache(): void;
}
