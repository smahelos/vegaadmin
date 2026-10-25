<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\Exceptions\ProductInUseException;
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
     * Whether an invoice line refers to the product, which keeps it from being deleted.
     */
    public function isUsedOnInvoices(ProductId $id): bool;

    /**
     * Delete product by ID.
     *
     * @throws ProductInUseException when an invoice line refers to the product
     */
    public function delete(ProductId $id): void;
}
