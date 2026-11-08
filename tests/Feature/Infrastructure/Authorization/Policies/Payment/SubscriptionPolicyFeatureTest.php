<?php

namespace Tests\Feature\Infrastructure\Authorization\Policies\Payment;

use App\Models\Subscription;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SubscriptionPolicyFeatureTest extends TestCase
{
    #[Test]
    public function policy_allows_user_to_update_own_subscription(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo('frontend.can_cancel_subscription');
        $subscriptionFactory = Subscription::factory();
        $subscriptionFactory = $subscriptionFactory->for($user);
        $subscription = $subscriptionFactory->create();

        $this->assertTrue(
            $user->can('update', $subscription),
        );
    }

    #[Test]
    public function policy_denies_updating_an_own_subscription_without_the_permission(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $subscriptionFactory = Subscription::factory();
        $subscriptionFactory = $subscriptionFactory->for($user);
        $subscription = $subscriptionFactory->create();

        $this->assertFalse(
            $user->can('update', $subscription),
        );
    }

    #[Test]
    public function policy_denies_updating_the_subscription_of_another_user(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $owner = $userFactory->create();
        $user->givePermissionTo('frontend.can_cancel_subscription');
        $subscriptionFactory = Subscription::factory();
        $subscriptionFactory = $subscriptionFactory->for($owner);
        $subscription = $subscriptionFactory->create();

        $this->assertFalse(
            $user->can('update', $subscription),
        );
    }

    #[Test]
    public function an_admin_updates_any_subscription_with_the_update_permission(): void
    {
        $userFactory = User::factory();
        $admin = $userFactory->create();
        $owner = $userFactory->create();
        $admin->givePermissionTo(
            Permission::findByName('can_update_subscription', 'backpack'),
        );
        $subscriptionFactory = Subscription::factory();
        $subscriptionFactory = $subscriptionFactory->for($owner);
        $subscription = $subscriptionFactory->create();

        $this->assertTrue(
            $admin->can('update', $subscription),
        );
    }
}
