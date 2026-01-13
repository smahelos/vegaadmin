<?php

namespace App\Infrastructure\Authorization\Policies\Payment;

use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    /**
     * Determine whether the user can view any subscription plans.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can view the subscription plan.
     */
    public function view(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can create subscription plans.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can update the subscription plan.
     */
    public function update(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can delete the subscription plan.
     */
    public function delete(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can restore the subscription plan.
     */
    public function restore(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }

    /**
     * Determine whether the user can permanently delete the subscription plan.
     */
    public function forceDelete(User $user, SubscriptionPlan $subscriptionPlan): bool
    {
        return $user->hasPermissionTo('can_create_edit_subscription_plan', 'backpack');
    }
}
