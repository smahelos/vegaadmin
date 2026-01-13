<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for Product DTO read operations.
 * Isolates Domain layer from Eloquent dependencies.
 */
interface ProductDtoReadRepositoryInterface
{
    /**
     * Get default product for user.
     */
    public function getDefaultProduct(UserId $userId): ?ProductDTO;

    /**
     * Find product by ID for any user (admin access).
     */
    public function findByIdAny(ProductId $id): ?ProductDTO;

    /**
     * Find product by ID for specific user.
     */
    public function findByIdForUser(ProductId $id, UserId $userId): ?ProductDTO;

    /**
     * Get user product count.
     */
    public function getUserProductCount(UserId $userId): int;
}
