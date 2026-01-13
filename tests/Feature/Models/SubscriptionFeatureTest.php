<?php

namespace Tests\Feature\Models;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function subscription_can_be_created_with_valid_data(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'amount' => 29.99,
            'currency' => 'EUR',
        ]);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($user->id, $subscription->user_id);
        $this->assertEquals($plan->id, $subscription->subscription_plan_id);
    }

    #[Test]
    public function subscription_has_user_relationship(): void
    {
        $subscription = Subscription::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $subscription->user());
        $this->assertInstanceOf(User::class, $subscription->user);
    }

    #[Test]
    public function subscription_has_subscription_plan_relationship(): void
    {
        $subscription = Subscription::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $subscription->subscriptionPlan());
        $this->assertInstanceOf(SubscriptionPlan::class, $subscription->subscriptionPlan);
    }

    #[Test]
    public function subscription_has_payments_relationship(): void
    {
        $subscription = Subscription::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $subscription->payments());
    }

    #[Test]
    public function subscription_active_scope_returns_only_active_subscriptions(): void
    {
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);
        $inactiveSubscription = Subscription::factory()->create(['status' => 'expired']);

        $activeSubscriptions = Subscription::active()->get();

        $this->assertCount(1, $activeSubscriptions);
        $this->assertEquals($activeSubscription->id, $activeSubscriptions->first()->id);
    }

    #[Test]
    public function subscription_expired_scope_returns_only_expired_subscriptions(): void
    {
        $activeSubscription = Subscription::factory()->create(['status' => 'active']);
        $expiredSubscription = Subscription::factory()->create(['status' => 'expired']);

        $expiredSubscriptions = Subscription::expired()->get();

        $this->assertCount(1, $expiredSubscriptions);
        $this->assertEquals($expiredSubscription->id, $expiredSubscriptions->first()->id);
    }

    #[Test]
    public function subscription_is_active_returns_correct_boolean(): void
    {
        // Test with status 'active' and future end date - should be active
        $activeSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(30)
        ]);
        
        // Test with status 'expired' - should not be active
        $expiredSubscription = Subscription::factory()->create(['status' => 'expired']);

        // Test with active status but past end date - should not be active due to date
        $expiredByDateSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDays(5)
        ]);

        // Test with active status and no end date - should be active
        $activeNoEndDateSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => null
        ]);

        $this->assertTrue($activeSubscription->isActive());
        $this->assertFalse($expiredSubscription->isActive());
        $this->assertFalse($expiredByDateSubscription->isActive());
        $this->assertTrue($activeNoEndDateSubscription->isActive());
    }

    #[Test]
    public function subscription_is_expired_returns_correct_boolean(): void
    {
        // Test with status 'active' and future end date - should not be expired
        $activeSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->addDays(30)
        ]);
        
        // Test with status 'expired' - should be expired regardless of end date
        $expiredSubscription = Subscription::factory()->create(['status' => 'expired']);

        // Test with active status but past end date - should be expired due to date
        $expiredByDateSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => now()->subDays(5)
        ]);

        // Test with active status and no end date - should not be expired
        $activeNoEndDateSubscription = Subscription::factory()->create([
            'status' => 'active',
            'ends_at' => null
        ]);

        $this->assertFalse($activeSubscription->isExpired());
        $this->assertTrue($expiredSubscription->isExpired());
        $this->assertTrue($expiredByDateSubscription->isExpired());
        $this->assertFalse($activeNoEndDateSubscription->isExpired());
    }

    #[Test]
    public function subscription_cancel_updates_status_to_cancelled(): void
    {
        $subscription = Subscription::factory()->create(['status' => 'active']);

        $result = $subscription->cancel();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $subscription->fresh()->status);
    }

    #[Test]
    public function subscription_activate_updates_status_to_active(): void
    {
        $subscription = Subscription::factory()->create(['status' => 'pending']);

        $result = $subscription->activate();

        $this->assertTrue($result);
        $this->assertEquals('active', $subscription->fresh()->status);
    }

    #[Test]
    public function subscription_days_until_expiration_returns_correct_value(): void
    {
        $futureDate = now()->addDays(15);
        $subscription = Subscription::factory()->create(['ends_at' => $futureDate]);

        $days = $subscription->daysUntilExpiration();

        $this->assertIsInt($days);
        $this->assertGreaterThanOrEqual(14, $days);
        $this->assertLessThanOrEqual(15, $days);
    }

    #[Test]
    public function subscription_is_in_trial_returns_correct_boolean(): void
    {
        $trialSubscription = Subscription::factory()->create([
            'trial_ends_at' => now()->addDays(7),
            'status' => 'active'
        ]);
        
        $regularSubscription = Subscription::factory()->create([
            'trial_ends_at' => null,
            'status' => 'active'
        ]);

        $this->assertTrue($trialSubscription->isInTrial());
        $this->assertFalse($regularSubscription->isInTrial());
    }
}
