<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use DomainException;

/**
 * Thrown when another business or contact of the user, archived ones included, has the same Czech IČO; it
 * carries that record, so the form can offer it or its restoration.
 */
final class DuplicateCompanyNumberException extends DomainException
{
    public function __construct(
        public readonly BusinessId|ContactId $existing,
    ) {
        parent::__construct("The Czech company number belongs to record {$existing->value} already");
    }
}
