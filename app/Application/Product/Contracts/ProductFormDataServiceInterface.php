<?php

declare(strict_types=1);

namespace App\Application\Product\Contracts;

/**
 * The choices of the product forms: reference data without an owner, the same for every user.
 */
interface ProductFormDataServiceInterface
{
    /**
     * @return array{product_categories: array<int, string>, tax_rates: array<int, string>}
     */
    public function getFormData(): array;

    /**
     * @return array<int, string> category names by id
     */
    public function getAllCategories(): array;
}
