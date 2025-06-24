<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface ProductsServiceInterface
{
    /**
     * Get all product categories
     * 
     * @return array
     */
    public function getAllCategories(): array;

    /**
     * Get all suppliers for the current user
     * 
     * @return array
     */
    public function getAllSuppliers(): array;

    /**
     * Delete cache for all categories
     * 
     * @return void
     */
    public function clearCategoriesCache(): void;

    /**
     * Handles product image processing
     * 
     * @param UploadedFile|null $image
     * @param string|null $oldImage
     * @return string|null
     */
    public function handleProductImage(?UploadedFile $image, ?string $oldImage = null): ?string;
}
