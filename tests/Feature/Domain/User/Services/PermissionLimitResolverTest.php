<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Services\PermissionLimitResolver;
use App\Models\EntityLimit;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class PermissionLimitResolverTest extends TestCase
{
    use RefreshDatabaseWithData;

    private PermissionLimitResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required permissions for testing
        Permission::create(['name' => 'can_create_edit_invoice', 'guard_name' => 'web']);
        Permission::create(['name' => 'can_use_api', 'guard_name' => 'web']);
        Permission::create(['name' => 'basic_user', 'guard_name' => 'web']);
        Permission::create(['name' => 'premium_user', 'guard_name' => 'web']);
        
        // Create basic role
        $basicRole = Role::create(['name' => 'basic', 'guard_name' => 'web']);
        $basicRole->givePermissionTo('can_create_edit_invoice');
        
        $this->resolver = app(PermissionLimitResolver::class);
    }

    #[Test]
    public function resolves_invoice_limit_for_basic_user(): void
    {
        // Create entity limit for basic permission
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

        $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        $this->assertEquals(10, $limit);
    }

    #[Test]
    public function user_without_permissions_gets_zero_limit(): void
    {
        $user = User::factory()->create();
        // User has no permissions

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        $this->assertEquals(0, $limit);
    }

    #[Test]
    public function resolves_highest_limit_for_user_with_multiple_permissions(): void
    {
        // Create multiple limits for different permissions
        EntityLimit::create([
            'permission_name' => 'basic_user',
            'entity_type' => 'invoice',
            'limit_value' => 5,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        EntityLimit::create([
            'permission_name' => 'premium_user',
            'entity_type' => 'invoice',
            'limit_value' => 25,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(['basic_user', 'premium_user']);

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        // Should return the highest limit (25)
        $this->assertEquals(25, $limit);
    }

    #[Test]
    public function returns_unlimited_for_api_permission(): void
    {
        // Create entity limit with very high value (simulating unlimited)
        EntityLimit::create([
            'permission_name' => 'can_use_api',
            'entity_type' => 'invoice',
            'limit_value' => 999999,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_use_api');

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        $this->assertEquals(999999, $limit);
    }

    #[Test]
    public function can_create_invoice_returns_true_when_user_has_permission(): void
    {
        // Create entity limit
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 5,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

        $canCreate = $this->resolver->canUserCreateEntity($user->id, 'invoice');

        $this->assertTrue($canCreate);
    }

    #[Test]
    public function can_create_invoice_returns_false_when_user_has_no_permission(): void
    {
        $user = User::factory()->create();
        // User has no permissions

        $canCreate = $this->resolver->canUserCreateEntity($user->id, 'invoice');

        $this->assertFalse($canCreate);
    }

    #[Test]
    public function resolves_user_limit_for_generic_permission(): void
    {
        EntityLimit::create([
            'permission_name' => 'basic_user',
            'entity_type' => 'client',
            'limit_value' => 15,
            'period_type' => 'daily',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('basic_user');

    $limit = $this->resolver->getUserLimit($user->id, 'client', 'count', 'daily');

        $this->assertEquals(15, $limit);
    }

    #[Test]
    public function returns_zero_for_non_existent_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('basic_user'); // This permission exists but has no limits

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        $this->assertEquals(0, $limit);
    }

    #[Test]
    public function handles_mixed_permission_guards(): void
    {
        // Create limits for web guard permission
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 20,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        $this->assertEquals(20, $limit);
    }

    #[Test]
    public function ignores_inactive_entity_limits(): void
    {
        // Create inactive limit
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 100,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => false, // Inactive
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

    $limit = $this->resolver->getUserLimit($user->id, 'invoice', 'count', 'monthly');

        // Should return 0 because the limit is inactive
        $this->assertEquals(0, $limit);
    }

    #[Test]
    public function gets_all_user_limits(): void
    {
        // Create multiple limits for different entities
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'client',
            'limit_value' => 50,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

    $allLimits = $this->resolver->getAllUserLimits($user->id);

        $this->assertArrayHasKey('invoice', $allLimits);
        $this->assertArrayHasKey('client', $allLimits);
        $this->assertEquals(10, $allLimits['invoice']['count']['monthly']);
        $this->assertEquals(50, $allLimits['client']['count']['monthly']);
    }

    #[Test]
    public function gets_subscription_limits_overview(): void
    {
        EntityLimit::create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 15,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo('can_create_edit_invoice');

    $limits = $this->resolver->getUserPermissionLimits($user->id, 'invoice');

    $this->assertIsArray($limits);
    $this->assertNotEmpty($limits);
    $this->assertArrayHasKey('permission', $limits[0]);
    $this->assertArrayHasKey('limits', $limits[0]);
    }

    #[Test]
    public function anonymous_user_gets_zero_limits(): void
    {
        $limit = $this->resolver->getUserLimit(null, 'invoice', 'count', 'monthly');

        $this->assertEquals(0, $limit);
    }

    #[Test]
    public function anonymous_user_cannot_create_entities(): void
    {
        $canCreate = $this->resolver->canUserCreateEntity(null, 'invoice');

        $this->assertFalse($canCreate);
    }
}
