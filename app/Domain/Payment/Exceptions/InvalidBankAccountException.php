<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use App\Domain\Payment\Enums\BankAccountProblem;
use DomainException;

final class InvalidBankAccountException extends DomainException
{
    public function __construct(
        public readonly BankAccountProblem $problem,
    ) {
        parent::__construct('Invalid bank account: ' . $problem->value);
    }
}
