<?php

declare(strict_types=1);

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

interface BankAccountReadRepositoryInterface
{
    /**
     * Null when the account does not exist or its owner is outside the scope; archived accounts are found.
     */
    public function findById(BankAccountId $id, ReadScope $scope): ?BankAccountDTO;

    /**
     * Empty when the owner is outside the scope.
     *
     * @return list<BankAccountDTO> archived included, in the order they were added
     */
    public function listForOwner(ReadScope $scope, AccountOwner $owner): array;

    public function findDefaultIdForOwner(ReadScope $scope, AccountOwner $owner): ?BankAccountId;
}
