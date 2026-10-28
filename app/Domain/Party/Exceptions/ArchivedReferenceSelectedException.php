<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use DomainException;

/**
 * Thrown when a document newly selects an archived business, contact or account; a document keeps a reference
 * that was archived after it was stored.
 */
final class ArchivedReferenceSelectedException extends DomainException
{
    public function __construct(
        public readonly BusinessId|ContactId|BankAccountId $reference,
    ) {
        parent::__construct('An archived ' . $reference::class . " {$reference->value} cannot be selected");
    }
}
