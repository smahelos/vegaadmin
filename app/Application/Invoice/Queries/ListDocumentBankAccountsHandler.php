<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class ListDocumentBankAccountsHandler
{
    public function __construct(
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    /** @return list<BankAccountDTO> */
    public function handle(ListDocumentBankAccounts $query, ReadScope $scope): array
    {
        return $this->accounts->listForOwner($scope, $query->accountOwner);
    }
}
