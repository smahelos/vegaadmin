<?php

namespace App\Domain\Product\Exceptions;

use App\Domain\Product\ValueObjects\ProductId;

/**
 * Domain exception thrown when a product cannot be found.
 * 
 * Used when:
 * - Product lookup by ID fails
 * - Product authorization fails
 * - Product access is denied for user
 */
class ProductNotFoundException extends \DomainException
{
    public static function forId(ProductId $productId): self
    {
        return new self("Product with ID {$productId->getValue()} not found.");
    }

    public static function forIdAndUser(ProductId $productId, int $userId): self
    {
        return new self("Product with ID {$productId->getValue()} not found for user {$userId}.");
    }

    public static function forSlug(string $slug): self
    {
        return new self("Product with slug '{$slug}' not found.");
    }
}
