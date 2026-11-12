<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

final class SetDefaultBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly UserWriteRepositoryInterface $users,
    ) {}

    /**
     * @throws BankAccountArchivedException
     */
    public function handle(SetDefaultBankAccount $command, WriteScope $scope): void
    {
        $this->users->lockForUpdate($command->owner);
        $account = $this->owned->get(
            $scope->read,
            $command->accountOwner,
            $command->id,
        );
        if ($account->isArchived()) {
            throw new BankAccountArchivedException($command->id);
        }

        $this->accountWriter->setDefaultForOwner($account->owner, $command->id);
    }
}
