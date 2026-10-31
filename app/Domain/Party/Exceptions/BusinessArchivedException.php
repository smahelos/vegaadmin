<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BusinessId;
use DomainException;

/**
 * Thrown when an archived business is chosen as the default.
 */
final class BusinessArchivedException extends DomainException
{
    public function __construct(BusinessId $id)
    {
        parent::__construct("Business {$id->value} is archived");
    }
}
