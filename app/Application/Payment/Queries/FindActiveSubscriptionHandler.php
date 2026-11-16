<?php

declare(strict_types=1);

namespace App\Application\Payment\Queries;

use App\Domain\Payment\Contracts\SubscriptionDtoReadRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionDTO;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class FindActiveSubscriptionHandler
{
    public function __construct(
        private readonly SubscriptionDtoReadRepositoryInterface $subscriptions,
    ) {}

    public function handle(FindActiveSubscription $query, ReadScope $scope): ?SubscriptionDTO
    {
        return $this->subscriptions->findActive($scope);
    }
}
