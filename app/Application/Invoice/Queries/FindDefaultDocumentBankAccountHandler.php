<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindDefaultDocumentBankAccountHandler
{
    public function __construct(
        private readonly BankAccountReadRepositoryInterface $accounts,
    ) {}

    public function handle(FindDefaultDocumentBankAccount $query, ReadScope $scope): ?BankAccountId
    {
        return $this->accounts->findDefaultIdForOwner($scope, $query->accountOwner);
    }
}
