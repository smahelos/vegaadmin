<?php

namespace App\Infrastructure\Authorization\Policies\Payment;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubscriptionPolicy
{
    /**
     * Determine whether the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription', 'backpack') ||
               $user->hasPermissionTo('frontend.can_view_subscription', 'web');
    }

    /**
     * Determine whether the user can view the subscription.
     */
    public function view(User $user, Subscription $subscription): bool
    {
        // Admin can view any subscription
        if ($user->hasPermissionTo('can_create_edit_subscription', 'backpack')) {
            return true;
        }

        // Users can view their own subscriptions
        return $user->id === $subscription->user_id &&
               $user->hasPermissionTo('frontend.can_view_subscription', 'web');
    }

    /**
     * Determine whether the user can create subscriptions.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription', 'backpack') ||
               $user->hasPermissionTo('frontend.can_create_subscription', 'web');
    }

    /**
     * Determine whether the user can update the subscription.
     */
    public function update(User $user, Subscription $subscription): bool
    {
        // Admin can update any subscription
        if ($user->hasPermissionTo('can_create_edit_subscription', 'backpack')) {
            return true;
        }

        // Users can cancel their own subscriptions
        if ($user->id === $subscription->user_id && 
            $user->hasPermissionTo('frontend.can_cancel_subscription', 'web')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the subscription.
     */
    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription', 'backpack');
    }

    /**
     * Determine whether the user can restore the subscription.
     */
    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription', 'backpack');
    }

    /**
     * Determine whether the user can permanently delete the subscription.
     */
    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription', 'backpack');
    }
}
