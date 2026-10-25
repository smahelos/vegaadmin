<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Product\ValueObjects\ProductId;
use DomainException;
use Throwable;

/**
 * A product on an invoice line stays: deleting it would take the line with it or orphan it.
 */
final class ProductInUseException extends DomainException
{
    public static function onInvoices(
        ProductId $productId,
        ?Throwable $previous = null,
    ): self {
        return new self(
            'Product ' . $productId->getValue() . ' is used on invoices and cannot be deleted',
            0,
            $previous,
        );
    }
}
