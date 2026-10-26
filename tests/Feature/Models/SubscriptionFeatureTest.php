<?php

namespace Tests\Feature\Models;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionFeatureTest extends TestCase
{
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

        $this->assertInstanceOf(BelongsTo::class, $subscription->user());
        $this->assertInstanceOf(User::class, $subscription->user);
    }

    #[Test]
    public function subscription_has_subscription_plan_relationship(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $subscription->subscriptionPlan());
        $this->assertInstanceOf(SubscriptionPlan::class, $subscription->subscriptionPlan);
    }

    #[Test]
    public function subscription_has_payments_relationship(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(HasMany::class, $subscription->payments());
    }

    #[Test]
    public function subscription_cancel_updates_status_to_cancelled(): void
    {
        $subscription = Subscription::factory()->create(['status' => 'active']);

        $result = $subscription->cancel();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $subscription->fresh()->status);
    }
}
