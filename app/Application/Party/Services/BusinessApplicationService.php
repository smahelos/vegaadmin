<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\Events\BusinessArchived;
use App\Domain\Party\Events\BusinessRestored;
use App\Domain\Party\Events\BusinessUpdated;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\Services\BusinessCreator;
use App\Domain\Party\Services\CapacityLimit;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;
use App\Domain\User\Enums\EntityType;
use App\Domain\User\ValueObjects\UserId;

/**
 * The user's own businesses. While any business is active, one of them is the default.
 */
final class BusinessApplicationService
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly BusinessCreator $creator,
        private readonly CapacityLimit $capacity,
        private readonly DuplicateFinder $duplicates,
        private readonly UserWriteRepositoryInterface $users,
        private readonly TransactionBoundaryInterface $transactions,
        private readonly EventPublisherInterface $events,
        private readonly ClockInterface $clock,
    ) {}

    public function create(
        UserId $userId,
        BusinessWriteData $data,
        ?BankAccountWriteData $firstAccount,
    ): BusinessDTO {
        return $this->transactions->transaction(fn (): BusinessDTO => $this->creator->create(
            $userId,
            $data,
            $firstAccount,
        ));
    }

    public function update(
        UserId $userId,
        BusinessId $id,
        BusinessWriteData $data,
    ): BusinessDTO {
        return $this->transactions->transaction(function () use ($userId, $id, $data): BusinessDTO {
            $this->get($userId, $id);
            $business = $this->businessWriter->update($id, $data);
            $this->events->publish(new BusinessUpdated($id, $userId));

            return $business;
        });
    }

    /**
     * Archiving the default business needs a new default among the other active ones; the last active business
     * leaves the account without a default.
     *
     * @throws DefaultBusinessRequiredException
     * @throws BusinessArchivedException when the new default is archived
     */
    public function archive(
        UserId $userId,
        BusinessId $id,
        ?BusinessId $newDefault,
    ): void {
        $this->transactions->transaction(function () use ($userId, $id, $newDefault): void {
            $this->users->lockForUpdate($userId);
            if ($this->get($userId, $id)->isArchived()) {
                return;
            }

            $default = $this->businesses->findDefaultIdForUser($userId);
            if ($default !== null && $default->equals($id)) {
                $replacement = $this->replacementDefault(
                    $userId,
                    $id,
                    $newDefault,
                );
                $this->businessWriter->setDefaultForUser($userId, $replacement);
                $this->events->publish(new DefaultBusinessChanged($replacement, $userId));
            }

            $this->businessWriter->archive($id, $this->clock->now());
            $this->events->publish(new BusinessArchived($id, $userId));
        });
    }

    /**
     * Restoring takes a place within the capacity limit; a business restored while none is active becomes the
     * default, otherwise the default stays.
     */
    public function restore(UserId $userId, BusinessId $id): void
    {
        $this->transactions->transaction(function () use ($userId, $id): void {
            if (! $this->get($userId, $id)->isArchived()) {
                return;
            }

            $this->capacity->assertCanActivate($userId, EntityType::BUSINESS);
            $isFirstActive = $this->businesses->countActiveForUser($userId) === 0;

            $this->businessWriter->restore($id);
            $this->events->publish(new BusinessRestored($id, $userId));

            if ($isFirstActive) {
                $this->businessWriter->setDefaultForUser($userId, $id);
                $this->events->publish(new DefaultBusinessChanged($id, $userId));
            }
        });
    }

    /**
     * @throws BusinessArchivedException
     */
    public function setDefault(UserId $userId, BusinessId $id): void
    {
        $this->transactions->transaction(function () use ($userId, $id): void {
            $this->users->lockForUpdate($userId);
            if ($this->get($userId, $id)->isArchived()) {
                throw new BusinessArchivedException($id);
            }

            $this->businessWriter->setDefaultForUser($userId, $id);
            $this->events->publish(new DefaultBusinessChanged($id, $userId));
        });
    }

    /**
     * @throws BusinessNotFoundException
     */
    public function get(UserId $userId, BusinessId $id): BusinessDTO
    {
        return $this->businesses->findByIdForUser($id, $userId)
            ?? throw new BusinessNotFoundException($id);
    }

    /**
     * @return list<BusinessDTO>
     */
    public function listActive(UserId $userId): array
    {
        return $this->businesses->listActiveForUser($userId);
    }

    /**
     * @return list<BusinessDTO>
     */
    public function listArchived(UserId $userId): array
    {
        return $this->businesses->listArchivedForUser($userId);
    }

    /**
     * Businesses the profile may duplicate; a blocking match is refused on save as well.
     *
     * @return list<DuplicatePartyDTO>
     */
    public function findDuplicates(
        UserId $userId,
        PartyProfile $profile,
        ?BusinessId $except,
    ): array {
        return $this->duplicates->find($profile, $this->businesses->listPossibleDuplicates(
            $userId,
            $profile,
            $except,
        ));
    }

    private function replacementDefault(
        UserId $userId,
        BusinessId $archived,
        ?BusinessId $newDefault,
    ): ?BusinessId {
        if ($newDefault === null || $newDefault->equals($archived)) {
            $othersActive = $this->businesses->countActiveForUser($userId) > 1;
            if ($othersActive) {
                throw new DefaultBusinessRequiredException($archived);
            }

            return null;
        }

        if ($this->get($userId, $newDefault)->isArchived()) {
            throw new BusinessArchivedException($newDefault);
        }

        return $newDefault;
    }
}
