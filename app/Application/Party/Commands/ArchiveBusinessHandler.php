<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\Events\BusinessArchived;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

/**
 * Archiving the default business needs a new default among the other active ones; the last active business leaves
 * the account without a default.
 */
final class ArchiveBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly UserWriteRepositoryInterface $users,
        private readonly EventPublisherInterface $events,
        private readonly ClockInterface $clock,
    ) {}

    /**
     * @throws DefaultBusinessRequiredException
     * @throws BusinessArchivedException when the new default is archived
     */
    public function handle(ArchiveBusiness $command, WriteScope $scope): void
    {
        $this->users->lockForUpdate($command->owner);
        $business = $this->businesses->findById($command->id, $scope->read)
            ?? throw new BusinessNotFoundException($command->id);
        if ($business->isArchived()) {
            return;
        }

        $default = $this->businesses->findDefaultId($scope->read);
        if ($default !== null && $default->equals($command->id)) {
            $replacement = $this->replacementDefault(
                $scope->read,
                $command->id,
                $command->newDefault,
            );
            $this->businessWriter->setDefaultForUser($command->owner, $replacement);
            $this->events->publish(
                new DefaultBusinessChanged($command->owner),
            );
        }

        $this->businessWriter->archive(
            $command->id,
            $scope,
            $this->clock->now(),
        );
        $this->events->publish(
            new BusinessArchived($command->owner),
        );
    }

    private function replacementDefault(
        ReadScope $scope,
        BusinessId $archived,
        ?BusinessId $newDefault,
    ): ?BusinessId {
        if ($newDefault === null || $newDefault->equals($archived)) {
            $othersActive = $this->businesses->countActive($scope) > 1;
            if ($othersActive) {
                throw new DefaultBusinessRequiredException($archived);
            }

            return null;
        }

        $business = $this->businesses->findById($newDefault, $scope)
            ?? throw new BusinessNotFoundException($newDefault);
        if ($business->isArchived()) {
            throw new BusinessArchivedException($newDefault);
        }

        return $newDefault;
    }
}
