<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;

class EloquentSubscriptionDtoWriteRepository implements SubscriptionDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionWriteRepository $subscriptionWriteRepository,
    ) {}

    public function activateById(int $subscriptionId, int $durationDays): bool
    {
        return $this->subscriptionWriteRepository->activateById($subscriptionId, $durationDays);
    }
}
