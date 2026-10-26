<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\User\ValueObjects\UserId;

interface BankAccountReadRepositoryInterface
{
    /**
     * Null when the account does not exist or its owner belongs to another user; archived accounts are found.
     */
    public function findByIdForUser(BankAccountId $id, UserId $userId): ?BankAccountDTO;

    /**
     * @return list<BankAccountDTO> archived included, in the order they were added
     */
    public function listForOwner(AccountOwner $owner): array;

    public function findDefaultIdForOwner(AccountOwner $owner): ?BankAccountId;
}
