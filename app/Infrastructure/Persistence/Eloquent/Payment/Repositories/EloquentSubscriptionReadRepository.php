<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\SubscriptionReadRepositoryInterface;
use App\Models\Subscription;

class EloquentSubscriptionReadRepository implements SubscriptionReadRepositoryInterface
{
    public function findById(int $id): ?Subscription
    {
        return Subscription::find($id);
    }
}
