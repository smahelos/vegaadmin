<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetBankAccountForEditHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
    ) {}

    public function handle(GetBankAccountForEdit $query, ReadScope $scope): BankAccountDTO
    {
        return $this->owned->get(
            $scope,
            $query->accountOwner,
            $query->id,
        );
    }
}
