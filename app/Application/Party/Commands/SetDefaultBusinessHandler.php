<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Contracts\UserWriteRepositoryInterface;

final class SetDefaultBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly UserWriteRepositoryInterface $users,
        private readonly EventPublisherInterface $events,
    ) {}

    /**
     * @throws BusinessArchivedException
     */
    public function handle(SetDefaultBusiness $command, WriteScope $scope): void
    {
        $this->users->lockForUpdate($command->owner);
        $business = $this->businesses->findById($command->id, $scope->read)
            ?? throw new BusinessNotFoundException($command->id);
        if ($business->isArchived()) {
            throw new BusinessArchivedException($command->id);
        }

        $this->businessWriter->setDefault($scope, $command->id);
        $this->events->publish(
            new DefaultBusinessChanged($command->owner),
        );
    }
}
