<?php

namespace Tests\Feature\Models;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSubscriptionFeatureTest extends TestCase
{
    #[Test]
    public function user_has_subscriptions_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasMany::class, $user->subscriptions());
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
}
