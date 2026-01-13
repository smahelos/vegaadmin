<?php

namespace App\Domain\Product\Exceptions;

/**
 * Domain exception thrown when product validation fails at domain level.
 * 
 * Used when:
 * - Business rules validation fails
 * - Domain constraints are violated
 * - Product data integrity issues
 */
class ProductValidationException extends \DomainException
{
    public static function invalidPrice(float $price): self
    {
        return new self("Invalid product price: {$price}. Price must be greater than 0.");
    }

    public static function invalidName(string $name): self
    {
        return new self("Invalid product name: '{$name}'. Name must be between 2 and 255 characters.");
    }

    public static function duplicateSlug(string $slug): self
    {
        return new self("Product with slug '{$slug}' already exists.");
    }

    public static function invalidCurrency(string $currency): self
    {
        return new self("Invalid currency code: '{$currency}'. Must be valid ISO currency code.");
    }

    public static function defaultProductDeletionNotAllowed(): self
    {
        return new self("Cannot delete the default product. Please set another product as default first.");
    }

    public static function noProductsFound(): self
    {
        return new self("No products found for this user.");
    }
}
