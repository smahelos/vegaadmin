<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Party\Support\OwnedBankAccounts;
use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\DefaultBankAccountRequiredException;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

/**
 * Archiving the default account needs a new default among the owner's other active accounts; the last active account
 * leaves the owner without a default.
 */
final class ArchiveBankAccountHandler
{
    public function __construct(
        private readonly OwnedBankAccounts $owned,
        private readonly BankAccountReadRepositoryInterface $accounts,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly UserWriteRepositoryInterface $users,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * @throws DefaultBankAccountRequiredException
     * @throws BankAccountArchivedException when the new default is archived
     */
    public function handle(ArchiveBankAccount $command, WriteScope $scope): void
    {
        $this->users->lockForUpdate($command->owner);
        $account = $this->owned->get(
            $scope->read,
            $command->accountOwner,
            $command->id,
        );
        if ($account->isArchived()) {
            return;
        }

        $default = $this->accounts->findDefaultIdForOwner($scope->read, $account->owner);
        if ($default !== null && $default->equals($command->id)) {
            $this->accountWriter->setDefaultForOwner(
                $scope,
                $account->owner,
                $this->replacementDefault(
                    $scope->read,
                    $account,
                    $command->newDefault,
                ),
            );
        }
        $this->accountWriter->archive(
            $command->id,
            $scope,
            $this->clock->now(),
        );
    }

    private function replacementDefault(
        ReadScope $scope,
        BankAccountDTO $archived,
        ?BankAccountId $newDefault,
    ): ?BankAccountId {
        $ownerAccounts = $this->accounts->listForOwner($scope, $archived->owner);
        $othersActive = array_filter(
            $ownerAccounts,
            fn (BankAccountDTO $other): bool => !$other->isArchived() && !$other->id->equals($archived->id),
        );
        if ($newDefault === null || $newDefault->equals($archived->id)) {
            if ($othersActive !== []) {
                throw new DefaultBankAccountRequiredException($archived->id);
            }

            return null;
        }

        foreach ($ownerAccounts as $candidate) {
            if (!$candidate->id->equals($newDefault)) {
                continue;
            }
            if ($candidate->isArchived()) {
                throw new BankAccountArchivedException($newDefault);
            }

            return $newDefault;
        }

        // An account of another owner is not a replacement, even within the same user
        throw new BankAccountNotFoundException($newDefault);
    }
}
