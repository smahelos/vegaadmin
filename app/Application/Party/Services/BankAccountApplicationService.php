<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Party\Contracts\BankAccountReadRepositoryInterface;
use App\Domain\Party\Contracts\BankAccountWriteRepositoryInterface;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DefaultBankAccountRequiredException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;

/**
 * Bank accounts of a business or a contact, written once for both owners. Each owner has at most one default
 * account, common to all document currencies.
 */
final class BankAccountApplicationService
{
    public function __construct(
        private readonly BankAccountReadRepositoryInterface $accounts,
        private readonly BankAccountWriteRepositoryInterface $accountWriter,
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly UserWriteRepositoryInterface $users,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * An account added to an owner without a default account becomes the default.
     */
    public function add(
        UserId $userId,
        AccountOwner $owner,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        return $this->transactions->transaction(function () use ($userId, $owner, $data): BankAccountDTO {
            $this->users->lockForUpdate($userId);
            $this->assertOwnedBy($userId, $owner);

            $account = $this->accountWriter->create($owner, $data);
            if ($this->accounts->findDefaultIdForOwner($owner) === null) {
                $this->accountWriter->setDefaultForOwner($owner, $account->id);
            }

            return $account;
        });
    }

    public function update(
        UserId $userId,
        BankAccountId $id,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        $this->get($userId, $id);

        return $this->accountWriter->update($id, $data);
    }

    /**
     * Archiving the default account needs a new default among the owner's other active accounts; the last active
     * account leaves the owner without a default.
     *
     * @throws DefaultBankAccountRequiredException
     * @throws BankAccountArchivedException when the new default is archived
     */
    public function archive(
        UserId $userId,
        BankAccountId $id,
        ?BankAccountId $newDefault,
    ): void {
        $this->transactions->transaction(function () use ($userId, $id, $newDefault): void {
            $this->users->lockForUpdate($userId);
            $account = $this->get($userId, $id);
            if ($account->isArchived()) {
                return;
            }

            $default = $this->accounts->findDefaultIdForOwner($account->owner);
            if ($default !== null && $default->equals($id)) {
                $this->accountWriter->setDefaultForOwner($account->owner, $this->replacementDefault($account, $newDefault));
            }

            $this->accountWriter->archive($id, $this->clock->now());
        });
    }

    /**
     * An account restored while its owner has no default account becomes the default.
     */
    public function restore(UserId $userId, BankAccountId $id): void
    {
        $this->transactions->transaction(function () use ($userId, $id): void {
            $this->users->lockForUpdate($userId);
            $account = $this->get($userId, $id);
            if (! $account->isArchived()) {
                return;
            }

            $this->accountWriter->restore($id);
            if ($this->accounts->findDefaultIdForOwner($account->owner) === null) {
                $this->accountWriter->setDefaultForOwner($account->owner, $id);
            }
        });
    }

    /**
     * @throws BankAccountArchivedException
     */
    public function setDefault(UserId $userId, BankAccountId $id): void
    {
        $account = $this->get($userId, $id);
        if ($account->isArchived()) {
            throw new BankAccountArchivedException($id);
        }

        $this->accountWriter->setDefaultForOwner($account->owner, $id);
    }

    /**
     * @throws BankAccountNotFoundException
     */
    public function get(UserId $userId, BankAccountId $id): BankAccountDTO
    {
        return $this->accounts->findByIdForUser($id, $userId)
            ?? throw new BankAccountNotFoundException($id);
    }

    /**
     * @return list<BankAccountDTO> archived included
     */
    public function listForOwner(UserId $userId, AccountOwner $owner): array
    {
        $this->assertOwnedBy($userId, $owner);

        return $this->accounts->listForOwner($owner);
    }

    public function findDefaultId(UserId $userId, AccountOwner $owner): ?BankAccountId
    {
        $this->assertOwnedBy($userId, $owner);

        return $this->accounts->findDefaultIdForOwner($owner);
    }

    /**
     * @throws BusinessNotFoundException
     * @throws ContactNotFoundException
     */
    private function assertOwnedBy(UserId $userId, AccountOwner $owner): void
    {
        if ($owner->businessId !== null && $this->businesses->findByIdForUser($owner->businessId, $userId) === null) {
            throw new BusinessNotFoundException($owner->businessId);
        }

        if ($owner->contactId !== null && $this->contacts->findByIdForUser($owner->contactId, $userId) === null) {
            throw new ContactNotFoundException($owner->contactId);
        }
    }

    private function replacementDefault(BankAccountDTO $archived, ?BankAccountId $newDefault): ?BankAccountId
    {
        $ownerAccounts = $this->accounts->listForOwner($archived->owner);
        $othersActive = array_filter(
            $ownerAccounts,
            fn (BankAccountDTO $other): bool => ! $other->isArchived() && ! $other->id->equals($archived->id),
        );

        if ($newDefault === null || $newDefault->equals($archived->id)) {
            if ($othersActive !== []) {
                throw new DefaultBankAccountRequiredException($archived->id);
            }

            return null;
        }

        foreach ($ownerAccounts as $candidate) {
            if (! $candidate->id->equals($newDefault)) {
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
