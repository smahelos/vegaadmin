<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\ContactId;
use DomainException;

/**
 * The same answer for a missing record and another user's record.
 */
final class ContactNotFoundException extends DomainException
{
    public function __construct(ContactId $id)
    {
        parent::__construct("Contact {$id->value} not found");
    }
}
