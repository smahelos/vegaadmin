<?php

namespace App\Contracts;

interface TaxesServiceInterface
{
    /**
     * Get all taxes as slug => name array
     * 
     * @return array
     */
    public function getAllTaxes(): array;

    /**
     * Get all taxes as id => name array for select fields
     * 
     * @return array
     */
    public function getAllTaxesForSelect(): array;

    /**
     * Delete all categories cache
     * 
     * @return void
     */
    public function clearCategoriesCache(): void;
}
