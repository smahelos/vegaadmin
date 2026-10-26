<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BankAccountId;
use DomainException;

/**
 * The same answer for a missing record and another user's record.
 */
final class BankAccountNotFoundException extends DomainException
{
    public function __construct(BankAccountId $id)
    {
        parent::__construct("Bank account {$id->value} not found");
    }
}
