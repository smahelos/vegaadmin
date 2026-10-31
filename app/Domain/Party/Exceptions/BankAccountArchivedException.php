<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BankAccountId;
use DomainException;

/**
 * Thrown when an archived bank account is chosen as the default.
 */
final class BankAccountArchivedException extends DomainException
{
    public function __construct(BankAccountId $id)
    {
        parent::__construct("Bank account {$id->value} is archived");
    }
}
