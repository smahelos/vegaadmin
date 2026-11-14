<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class CreateSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionDtoWriteRepositoryInterface $subscriptionWriter,
    ) {}

    public function handle(CreateSubscription $command, WriteScope $scope): SubscriptionDTO
    {
        return $this->subscriptionWriter->create($scope, $command->data);
    }
}
