<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use DomainException;

/**
 * Thrown when a filled address has no country.
 */
final class InvalidAddressException extends DomainException
{
    public function __construct()
    {
        parent::__construct('An address with a street, city or postal code needs a country');
    }
}
