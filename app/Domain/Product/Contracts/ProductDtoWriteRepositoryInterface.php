<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;

/**
 * Contract for Product DTO write operations.
 * Isolates Domain layer from Eloquent dependencies.
 */
interface ProductDtoWriteRepositoryInterface
{
    /**
     * Create new product from write data.
     */
    public function create(ProductWriteData $writeData): ProductDTO;

    /**
     * Update existing product from write data.
     */
    public function update(ProductId $id, ProductWriteData $writeData): ProductDTO;

    /**
     * Delete product by ID.
     */
    public function delete(ProductId $id): void;

    /**
     * Delete multiple products by user ID.
     */
    public function deleteByUserId(int $userId): int;
}
