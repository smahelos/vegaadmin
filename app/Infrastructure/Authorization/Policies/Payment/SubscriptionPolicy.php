<?php

namespace App\Infrastructure\Authorization\Policies\Payment;

use App\Domain\User\Enums\Permission;
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
        if ($user->checkPermissionTo(Permission::SUBSCRIPTION_UPDATE, 'backpack')) {
            return true;
        }

        // Users can cancel their own subscriptions
        if (
            $user->id === $subscription->user_id
            && $user->checkPermissionTo(Permission::SUBSCRIPTION_CANCEL, 'web')
        ) {
            return true;
        }

        return false;
    }
}
