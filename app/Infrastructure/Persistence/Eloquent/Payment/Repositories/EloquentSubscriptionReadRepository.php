<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface;
use App\Models\Subscription;

class EloquentSubscriptionReadRepository implements SubscriptionReadRepositoryInterface
{
    public function findById(int $id): ?Subscription
    {
        // The activation after a paid notification reads the plan's billing period.
        $subscriptionQuery = Subscription::with('subscriptionPlan');

        return $subscriptionQuery->find($id);
    }
}
