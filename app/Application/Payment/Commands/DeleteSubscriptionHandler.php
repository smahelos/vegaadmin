<?php

declare(strict_types=1);

namespace App\Application\Payment\Commands;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Domain\Payment\Exceptions\SubscriptionInUseException;
use App\Domain\Payment\Exceptions\SubscriptionNotFoundException;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;

final class DeleteSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionDtoWriteRepositoryInterface $subscriptionWriter,
    ) {}

    /**
     * @throws SubscriptionInUseException
     * @throws SubscriptionNotFoundException
     */
    public function handle(DeleteSubscription $command, WriteScope $scope): void
    {
        $this->subscriptionWriter->delete($command->id, $scope);
    }
}
