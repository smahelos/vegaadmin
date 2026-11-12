<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class UpdateBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
    ) {}

    public function handle(UpdateBankAccount $command, WriteScope $scope): BankAccountDTO
    {
        $this->owned->get(
            $scope->read,
            $command->accountOwner,
            $command->id,
        );

        return $this->accountWriter->update(
            $command->id,
            $scope,
            $command->data,
        );
    }
}
