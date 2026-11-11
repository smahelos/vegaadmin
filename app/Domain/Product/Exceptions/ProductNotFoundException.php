<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Product\ValueObjects\ProductId;
use DomainException;

/**
 * The same answer for a missing product and another user's product.
 */
final class ProductNotFoundException extends DomainException
{
    public function __construct(ProductId $id)
    {
        parent::__construct("Product {$id->getValue()} not found");
    }
}
