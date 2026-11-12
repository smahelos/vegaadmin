<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\BusinessWriteRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\Events\BusinessUpdated;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;

final class UpdateBusinessHandler
{
    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly BusinessWriteRepositoryInterface $businessWriter,
        private readonly EventPublisherInterface $events,
    ) {}

    public function handle(UpdateBusiness $command, WriteScope $scope): BusinessDTO
    {
        if ($this->businesses->findById($command->id, $scope->read) === null) {
            throw new BusinessNotFoundException($command->id);
        }

        $business = $this->businessWriter->update(
            $command->id,
            $scope,
            $command->data,
        );
        $this->events->publish(
            new BusinessUpdated($command->owner),
        );

        return $business;
    }
}
