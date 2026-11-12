<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

/**
 * An account added to an owner without a default account becomes the default.
 */
final class CreateBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountReadRepositoryInterface $accounts,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    public function handle(CreateBankAccount $command, WriteScope $scope): BankAccountDTO
    {
        $this->users->lockForUpdate($command->owner);
        $this->owned->assertOwner($scope->read, $command->accountOwner);
        $account = $this->accountWriter->create($command->accountOwner, $command->data);
        if ($this->accounts->findDefaultIdForOwner($scope->read, $command->accountOwner) === null) {
            $this->accountWriter->setDefaultForOwner($command->accountOwner, $account->id);
        }

        return $account;
    }
}
