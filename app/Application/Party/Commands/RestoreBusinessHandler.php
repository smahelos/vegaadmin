<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\Events\BusinessRestored;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

/**
 * Restoring takes a place within the capacity limit; a business restored while none is active becomes the default,
 * otherwise the default stays.
 */
final class RestoreBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly EntityLimit $limits,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(RestoreBusiness $command, WriteScope $scope): void
    {
        $business = $this->businesses->findById($command->id, $scope->read)
            ?? throw new BusinessNotFoundException($command->id);
        if (!$business->isArchived()) {
            return;
        }

        $this->limits->assertCanCreate($command->owner, LimitedEntity::BUSINESS);
        $isFirstActive = $this->businesses->countActiveForUpdate($scope->read) === 0;

        $this->businessWriter->restore($command->id, $scope);
        $this->events->publish(
            new BusinessRestored($command->owner),
        );

        if ($isFirstActive) {
            $this->businessWriter->setDefault($scope, $command->id);
            $this->events->publish(
                new DefaultBusinessChanged($command->owner),
            );
        }
    }
}
