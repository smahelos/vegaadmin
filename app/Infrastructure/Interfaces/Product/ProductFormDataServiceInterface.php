<?php

namespace App\Infrastructure\Interfaces\Product;

/**
 * Interface for Product-specific form data operations.
 * Handles categories and tax rates for Product forms.
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
}
