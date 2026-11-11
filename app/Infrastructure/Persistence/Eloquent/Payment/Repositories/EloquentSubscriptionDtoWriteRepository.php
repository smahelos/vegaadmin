<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoWriteRepositoryInterface;
use DateTimeImmutable;

class EloquentSubscriptionDtoWriteRepository implements SubscriptionDtoWriteRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionWriteRepository $subscriptionWriteRepository,
    ) {}

    public function activateById(
        int $subscriptionId,
        DateTimeImmutable $starts,
        DateTimeImmutable $ends,
    ): bool {
        return $this->subscriptionWriteRepository->activateById(
            $subscriptionId,
            $starts,
            $ends,
        );
    }
}
