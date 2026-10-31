<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BusinessId;
use DomainException;

/**
 * Thrown when a business without street, city, postal code or country is to supply an invoice; the user
 * completes it in the business's edit form.
 */
final class IncompleteBusinessAddressException extends DomainException
{
    public function __construct(
        BusinessId $business,
    ) {
        parent::__construct("Business {$business->value} has no complete address");
    }
}
