<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BusinessId;
use DomainException;

/**
 * The same answer for a missing record and another user's record.
 */
final class BusinessNotFoundException extends DomainException
{
    public function __construct(BusinessId $id)
    {
        parent::__construct("Business {$id->value} not found");
    }
}
