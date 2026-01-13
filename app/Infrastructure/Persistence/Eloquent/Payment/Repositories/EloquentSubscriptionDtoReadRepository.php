<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;

class EloquentSubscriptionDtoReadRepository implements SubscriptionDtoReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionReadRepository $subscriptionReadRepository,
        private readonly EloquentSubscriptionMapper $mapper
    ) {
    }

    public function findById(int $id): ?SubscriptionDTO
    {
        $subscriptionDto = $this->subscriptionReadRepository->findById($id);
        return $subscriptionDto ? $this->mapper->toDto($subscriptionDto) : null;
    }
}
