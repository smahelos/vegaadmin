<?php

namespace App\Infrastructure\Authorization\Policies\Payment;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Admin can update any subscription
        if ($user->hasPermissionTo('can_update_subscription', 'backpack')) {
            return true;
        }

        // Users can cancel their own subscriptions
        if (
            $user->id === $subscription->user_id
            && $user->hasPermissionTo('frontend.can_cancel_subscription', 'web')
        ) {
            return true;
        }

        return false;
    }
}
