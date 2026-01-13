<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductWriteData;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Domain service interface for Product business operations.
 * Contains only pure business logic, no Infrastructure dependencies.
 */
interface ProductServiceInterface
{
    /**
     * Create new product with business logic using DTOs
     */
    public function createProduct(ProductWriteData $writeData, UserId $userId): ProductDTO;

    /**
     * Update product with business logic using DTOs
     */
    public function updateProduct(ProductId $productId, ProductWriteData $writeData): ProductDTO;

    /**
     * Delete product using DTOs
     */
    public function deleteProduct(ProductId $productId): void;

    /**
     * Find product by ID for any user (admin access)
     */
    public function findProductAny(ProductId $productId): ?ProductDTO;

    /**
     * Find product by ID for specific user
     */
    public function findProductForUser(ProductId $productId, UserId $userId): ?ProductDTO;

    /**
     * Get default product for user
     */
    public function getDefaultProduct(UserId $userId): ?ProductDTO;

    /**
     * Get user's product count
     */
    public function getUserProductCount(UserId $userId): int;
}
