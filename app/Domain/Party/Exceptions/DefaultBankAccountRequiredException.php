<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BankAccountId;
use DomainException;

/**
 * Thrown when the default account is archived while other accounts of its owner stay active and no new default
 * was chosen.
 */
final class DefaultBankAccountRequiredException extends DomainException
{
    public function __construct(BankAccountId $id)
    {
        parent::__construct("Archiving the default bank account {$id->value} needs another active account as the default");
    }
}
