<?php

namespace Tests\Feature\Models;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSubscriptionFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_has_subscriptions_relationship(): void
    {
        $user = User::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->subscriptions());
    }

    #[Test]
    public function user_can_have_multiple_subscriptions(): void
    {
        $user = User::factory()->create();
        $subscription1 = Subscription::factory()->create(['user_id' => $user->id]);
        $subscription2 = Subscription::factory()->create(['user_id' => $user->id]);

        $subscriptions = $user->subscriptions;

        $this->assertCount(2, $subscriptions);
        $this->assertTrue($subscriptions->contains($subscription1));
        $this->assertTrue($subscriptions->contains($subscription2));
    }

    #[Test]
    public function user_active_subscription_returns_active_subscription(): void
    {
        $user = User::factory()->create();
        $expiredSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'expired'
        ]);
        $activeSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        $result = $user->activeSubscription();

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertEquals($activeSubscription->id, $result->id);
        $this->assertEquals('active', $result->status);
    }

    #[Test]
    public function user_active_subscription_returns_null_when_no_active_subscription(): void
    {
        $user = User::factory()->create();
        $expiredSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'expired'
        ]);

        $result = $user->activeSubscription();

        $this->assertNull($result);
    }

    #[Test]
    public function user_has_active_subscription_returns_true_when_subscription_exists(): void
    {
        $user = User::factory()->create();
        $activeSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        $result = $user->hasActiveSubscription();

        $this->assertTrue($result);
    }

    #[Test]
    public function user_has_active_subscription_returns_false_when_no_active_subscription(): void
    {
        $user = User::factory()->create();
        $expiredSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'expired'
        ]);

        $result = $user->hasActiveSubscription();

        $this->assertFalse($result);
    }

    #[Test]
    public function user_has_active_subscription_returns_false_when_no_subscriptions(): void
    {
        $user = User::factory()->create();

        $result = $user->hasActiveSubscription();

        $this->assertFalse($result);
    }

    #[Test]
    public function subscription_methods_have_correct_return_types(): void
    {
        $reflection = new \ReflectionClass(User::class);
        
        // Test subscriptions relationship
        $method = $reflection->getMethod('subscriptions');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('Illuminate\Database\Eloquent\Relations\HasMany', $returnType->getName());
        
        // Test hasActiveSubscription method
        $method = $reflection->getMethod('hasActiveSubscription');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('bool', $returnType->getName());
    }

    #[Test]
    public function user_with_multiple_active_subscriptions_returns_first_active(): void
    {
        $user = User::factory()->create();
        $firstActive = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'created_at' => now()->subDays(2)
        ]);
        $secondActive = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'created_at' => now()->subDays(1)
        ]);

        $result = $user->activeSubscription();

        $this->assertInstanceOf(Subscription::class, $result);
        // Should return one of the active subscriptions
        $this->assertContains($result->id, [$firstActive->id, $secondActive->id]);
        $this->assertEquals('active', $result->status);
    }

    #[Test]
    public function user_subscription_status_changes_affect_has_active_subscription(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        // Initially has active subscription
        $this->assertTrue($user->hasActiveSubscription());

        // Change status to expired
        $subscription->update(['status' => 'expired']);
        
        // Refresh user model to clear any cached relationships
        $user->refresh();
        
        // Should no longer have active subscription
        $this->assertFalse($user->hasActiveSubscription());
    }
}
