<?php

namespace App\Infrastructure\Persistence\Eloquent\Payment\Repositories;

use App\Application\Payment\Contracts\SubscriptionWriteRepositoryInterface;
use App\Models\Subscription;
use DateTimeImmutable;

class EloquentSubscriptionWriteRepository implements SubscriptionWriteRepositoryInterface
{
    public function activateById(
        int $subscriptionId,
        DateTimeImmutable $starts,
        DateTimeImmutable $ends,
    ): bool {
        $subscription = Subscription::find($subscriptionId);
        if (!$subscription) {
            return false;
        }

        return $subscription->update(
            [
                'status' => 'active',
                'starts_at' => $starts,
                'ends_at' => $ends,
            ],
        );
    }
}
