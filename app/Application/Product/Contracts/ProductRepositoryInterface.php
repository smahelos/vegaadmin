<?php

namespace App\Application\Product\Contracts;

use App\Models\Product;

interface ProductRepositoryInterface
{
    /**
     * Create new product
     */
    public function create(array $data): Product;
}
