<?php

namespace App\Domain\Invoice\Exceptions;

use App\Domain\Invoice\ValueObjects\InvoiceId;

/**
 * Domain exception thrown when an invoice cannot be found.
 * 
 * Used when:
 * - Invoice lookup by ID fails
 * - Invoice authorization fails
 * - Invoice access is denied for user
 */
class InvoiceNotFoundException extends \DomainException
{
    public static function forId(InvoiceId $invoiceId): self
    {
        return new self("Invoice with ID {$invoiceId->getValue()} not found.");
    }

    public static function forIdAndUser(InvoiceId $invoiceId, int $userId): self
    {
        return new self("Invoice with ID {$invoiceId->getValue()} not found for user {$userId}.");
    }

    public static function forSlug(string $slug): self
    {
        return new self("Invoice with slug '{$slug}' not found.");
    }
}
