<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use DomainException;

final class InvalidVariableSymbolException extends DomainException
{
    public function __construct(string $value)
    {
        parent::__construct('Invalid variable symbol: ' . $value);
    }
}
