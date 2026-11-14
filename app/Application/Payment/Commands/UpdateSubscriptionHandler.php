<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\Exceptions\SubscriptionNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class UpdateSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionDtoWriteRepositoryInterface $subscriptionWriter,
    ) {}

    /**
     * @throws SubscriptionNotFoundException
     */
    public function handle(UpdateSubscription $command, WriteScope $scope): SubscriptionDTO
    {
        return $this->subscriptionWriter->update(
            $command->id,
            $scope,
            $command->data,
        );
    }
}
