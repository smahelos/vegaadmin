<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Product\ValueObjects\ProductId;
use DomainException;

/**
 * A product on an invoice line stays: deleting it would take the line with it or orphan it.
 */
final class ProductInUseException extends DomainException
{
    public static function onInvoices(ProductId $productId): self
    {
        return new self('Product ' . $productId->getValue() . ' is used on invoices and cannot be deleted');
    }
}
