<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindDefaultBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    public function handle(FindDefaultBankAccount $query, ReadScope $scope): ?BankAccountId
    {
        $this->owned->assertOwner($scope, $query->accountOwner);

        return $this->accounts->findDefaultIdForOwner($scope, $query->accountOwner);
    }
}
