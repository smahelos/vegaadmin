<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\ContactId;
use DomainException;

/**
 * Thrown when a document party linked to a contact is given another registration number than the contact has:
 * the party is then another company, entered without the contact as a one-off party.
 */
final class ContactIdentityChangedException extends DomainException
{
    public function __construct(
        ContactId $contact,
    ) {
        parent::__construct("The document party is not registered under the number of contact {$contact->value}");
    }
}
