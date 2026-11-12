<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListBankAccountsHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    /**
     * @return list<BankAccountDTO> archived included, in the order they were added
     */
    public function handle(ListBankAccounts $query, ReadScope $scope): array
    {
        $this->owned->assertOwner($scope, $query->accountOwner);

        return $this->accounts->listForOwner($scope, $query->accountOwner);
    }
}
