<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface;
use App\Models\Subscription;
use Carbon\CarbonImmutable;

class EloquentSubscriptionWriteRepository implements SubscriptionWriteRepositoryInterface
{
    public function activateById(int $subscriptionId, int $durationDays): bool
    {
        $subscription = Subscription::find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        $now = CarbonImmutable::now();

        return $subscription->update(
            [
                'status' => 'active',
                'starts_at' => $now,
                'ends_at' => $now->addDays($durationDays),
            ],
        );
    }
}
