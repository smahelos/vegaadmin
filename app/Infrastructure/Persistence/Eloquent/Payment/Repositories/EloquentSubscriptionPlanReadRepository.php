<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Domain\Payment\Contracts\SubscriptionPlanReadRepositoryInterface;
use App\Domain\Payment\DTO\SubscriptionPlanDTO;
use App\Domain\Payment\ValueObjects\SubscriptionPlanId;
use App\Infrastructure\Persistence\Eloquent\Payment\Mappers\EloquentSubscriptionMapper;
use App\Models\SubscriptionPlan;

final class EloquentSubscriptionPlanReadRepository implements SubscriptionPlanReadRepositoryInterface
{
    public function __construct(
        private readonly EloquentSubscriptionMapper $mapper,
    ) {}

    public function findById(SubscriptionPlanId $id): ?SubscriptionPlanDTO
    {
        $plan = SubscriptionPlan::find($id->value);

        return $plan === null ? null : $this->mapper->toPlanDto($plan);
    }
}
