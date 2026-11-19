<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Payment\Enums\SubscriptionStatus;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;
use App\Models\Subscription;

final class EloquentSubscriptionDtoReadRepository implements SubscriptionDtoReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionReadRepository $subscriptionReadRepository,
        private readonly EloquentSubscriptionMapper $mapper,
    ) {}

    public function findById(int $id): ?SubscriptionDTO
    {
        $subscriptionDto = $this->subscriptionReadRepository->findById($id);

        return $subscriptionDto ? $this->mapper->toDto($subscriptionDto) : null;
    }

    public function findActive(ReadScope $scope): ?SubscriptionDTO
    {
        $subscriptionQuery = Subscription::with('subscriptionPlan');
        $subscriptionQuery->where('user_id', $scope->owner->value);
        $subscriptionQuery->where('status', SubscriptionStatus::ACTIVE->value);
        $subscription = $subscriptionQuery->first();

        return $subscription === null ? null : $this->mapper->toDto($subscription);
    }
}
