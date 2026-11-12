<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

/**
 * An account restored while its owner has no default account becomes the default.
 */
final class RestoreBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountReadRepositoryInterface $accounts,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    public function handle(RestoreBankAccount $command, WriteScope $scope): void
    {
        $this->users->lockForUpdate($command->owner);
        $account = $this->owned->get(
            $scope->read,
            $command->accountOwner,
            $command->id,
        );
        if (!$account->isArchived()) {
            return;
        }

        $this->accountWriter->restore($command->id, $scope);
        if ($this->accounts->findDefaultIdForOwner($scope->read, $account->owner) === null) {
            $this->accountWriter->setDefaultForOwner($account->owner, $command->id);
        }
    }
}
