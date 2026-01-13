<?php

namespace Tests\Feature\Infrastructure\Authorization\Policies\Payment;

use App\Models\Subscription;
use App\Models\User;
use App\Infrastructure\Authorization\Policies\Payment\SubscriptionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Spatie\Permission\Models\Permission;

class SubscriptionPolicyFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

    protected User $user;
    private SubscriptionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new SubscriptionPolicy();
        
        // Set up frontend test environment with roles and permissions
        $this->setUpFrontendTestEnvironment();

        // Create permission
        $permission = Permission::firstOrCreate([
            'name' => 'frontend.can_create_subscription', 
            'guard_name' => 'web'
        ]);
        $permission2 = Permission::firstOrCreate([
            'name' => 'frontend.can_view_subscription', 
            'guard_name' => 'web'
        ]);
        
        $this->user->givePermissionTo($permission);
        $this->user->givePermissionTo($permission2);
    }

    #[Test]
    public function policy_allows_user_to_view_own_subscription(): void
    {
        $subscription = Subscription::factory()->for($this->user)->create();

        $this->actingAs($this->user);

        $result = $this->policy->view($this->user, $subscription);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function policy_denies_user_to_view_other_users_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $subscription = Subscription::factory()->for($user2)->create();

        $this->actingAs($user1);
        
        $result = $this->policy->view($user1, $subscription);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function policy_allows_user_to_update_own_subscription(): void
    {
        $user = User::factory()->create();
        
        // Give user permission to cancel/update subscriptions
        $user->givePermissionTo('frontend.can_cancel_subscription');
        
        $subscription = Subscription::factory()->for($user)->create();

        $this->actingAs($user);
        
        $result = $this->policy->update($user, $subscription);
        
        $this->assertTrue($result);
    }

    #[Test]
    public function policy_denies_user_to_update_other_users_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $subscription = Subscription::factory()->for($user2)->create();

        $this->actingAs($user1);
        
        $result = $this->policy->update($user1, $subscription);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function policy_denies_normal_user_to_delete_subscription(): void
    {
        $user = User::factory()->create();
        
        // Normal users cannot delete subscriptions (only admins with backpack permissions can)
        $subscription = Subscription::factory()->for($user)->create();

        $this->actingAs($user);
        
        $result = $this->policy->delete($user, $subscription);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function policy_denies_user_to_delete_other_users_subscription(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $subscription = Subscription::factory()->for($user2)->create();

        $this->actingAs($user1);
        
        $result = $this->policy->delete($user1, $subscription);
        
        $this->assertFalse($result);
    }

    #[Test]
    public function policy_integrates_with_laravel_authorization(): void
    {
        $user = User::factory()->create();
        
        // Give user necessary permissions for web guard
        $user->givePermissionTo('frontend.can_view_subscription');
        $user->givePermissionTo('frontend.can_cancel_subscription');
        
        $subscription = Subscription::factory()->for($user)->create();

        $this->actingAs($user);
        
        // Test using Laravel's authorization system
        $this->assertTrue($user->can('view', $subscription));
        $this->assertTrue($user->can('update', $subscription));
        
        // Delete requires admin permission which this user doesn't have
        $this->assertFalse($user->can('delete', $subscription));
    }

    #[Test]
    public function policy_denies_unauthorized_access_through_laravel_authorization(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $subscription = Subscription::factory()->for($user2)->create();

        $this->actingAs($user1);
        
        // Test using Laravel's authorization system
        $this->assertFalse($user1->can('view', $subscription));
        $this->assertFalse($user1->can('update', $subscription));
        $this->assertFalse($user1->can('delete', $subscription));
    }
}
