<?php

namespace App\Contracts;

interface StatusServiceInterface
{
    /**
     * Get all status categories
     * 
     * @return array
     */
    public function getAllCategories(): array;

    /**
     * Delete all categories cache
     * 
     * @return void
     */
    public function clearCategoriesCache(): void;
}
