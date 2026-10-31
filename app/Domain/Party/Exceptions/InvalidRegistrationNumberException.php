<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\Enums\RegistrationNumberProblem;
use DomainException;

final class InvalidRegistrationNumberException extends DomainException
{
    public function __construct(
        public readonly RegistrationNumberProblem $problem,
    ) {
        parent::__construct('Invalid registration number: ' . $problem->value);
    }
}
