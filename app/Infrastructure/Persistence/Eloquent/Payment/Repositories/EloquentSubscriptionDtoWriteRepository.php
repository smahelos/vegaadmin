<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;

class EloquentSubscriptionDtoWriteRepository implements SubscriptionDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionWriteRepository $subscriptionWriteRepository,
        private readonly EloquentSubscriptionMapper $mapper
    ) {
    }

    public function activateById(int $subscriptionId, int $durationDays): bool
    {
        return $this->subscriptionWriteRepository->activateById($subscriptionId, $durationDays);
    }
}
