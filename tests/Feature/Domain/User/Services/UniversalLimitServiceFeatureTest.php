<?php

namespace Tests\Feature\Domain\User\Services;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\EntityLimitUsage;
use App\Models\User;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature test for UniversalLimitService with permission-based logic
 * Tests real business logic with database operations
 */
class UniversalLimitServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    private UniversalLimitService $service;
    private User $user;
    private EntityLimit $monthlyInvoiceLimit;
    
    // Disable automatic database seeding - this test creates its own data
    protected bool $seedDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(UniversalLimitService::class);
        
        // Create test user with unique name
        $this->user = User::create([
            'name' => 'Test User ' . uniqid(),
            'email' => 'test' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create permission for testing
        $permission = Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_invoice',
            'guard_name' => 'web'
        ]);
        
        // Give user the permission
        $this->user->givePermissionTo($permission);
        
        // Create entity limit with permission-based structure
        $this->monthlyInvoiceLimit = EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 10,
            'description' => 'Monthly invoice creation limit',
            'is_active' => true
        ]);
    }

    #[Test]
    public function check_limit_returns_allowed_when_under_limit(): void
    {
        $result = $this->service->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals('within_limit', $result['reason']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(0, $result['current_usage']); // Service returns number, not array
        $this->assertEquals(10, $result['remaining']);
    }

    #[Test]
    public function check_limit_returns_not_allowed_when_at_limit(): void
    {
        // Record usage to reach the limit
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1);
        }
        
        $result = $this->service->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals('limit_exceeded', $result['reason']);
        $this->assertEquals(10, $result['limit']);
        $this->assertEquals(10, $result['current_usage']);
        $this->assertEquals(0, $result['remaining']);
    }

    #[Test]
    public function check_limit_returns_no_permission_when_no_limit_configured(): void
    {
        $result = $this->service->checkLimit($this->user->id, 'client', 'count', 'monthly');
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals('no_permission', $result['reason']);
        $this->assertEquals(0, $result['limit']);
    }

    #[Test]
    public function record_usage_increments_count_correctly(): void
    {
        $this->assertTrue($this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1));
        
        // Verify usage was recorded
        $usage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly'
        ])->first();
        
        $this->assertNotNull($usage);
        $this->assertEquals(1, $usage->current_value);
    }

    #[Test]
    public function record_usage_increments_value_for_value_based_limits(): void
    {
        // Create a value-based limit
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'metric_type' => 'value',
            'period_type' => 'monthly',
            'limit_value' => 100000,
            'description' => 'Monthly invoice value limit',
            'is_active' => true
        ]);

        $this->assertTrue($this->service->recordUsage($this->user->id, 'invoice', 'value', 'monthly', 'web', 5000));

        // Verify usage was recorded
        $usage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'invoice',
            'metric_type' => 'value',
            'period_type' => 'monthly'
        ])->first();
        
        $this->assertNotNull($usage);
        $this->assertEquals(5000, $usage->current_value);
    }

    #[Test]
    public function record_usage_works_even_for_inactive_limit(): void
    {
        // Deactivate the limit
        $this->monthlyInvoiceLimit->update(['is_active' => false]);
        
        // Should still work because user has permission (independent of limit status)
        $result = $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1);

        $this->assertTrue($result);
        
        // Verify usage was recorded
        $usage = EntityLimitUsage::where([
            'user_id' => $this->user->id,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly'
        ])->first();
        
        $this->assertNotNull($usage);
        $this->assertEquals(1, $usage->current_value);
    }

    #[Test]
    public function record_usage_returns_false_for_user_without_permission(): void
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();

        $result = $this->service->recordUsage($userWithoutPermission->id, 'invoice', 'count', 'monthly', 'web', 1);

        $this->assertFalse($result);
        
        // Verify no usage was recorded
        $usage = EntityLimitUsage::where([
            'user_id' => $userWithoutPermission->id,
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly'
        ])->first();
        
        $this->assertNull($usage);
    }

    #[Test]
    public function get_usage_statistics_returns_correct_data(): void
    {
        // Record some usage
        $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1);
        $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1);

        $stats = $this->service->getUsageStatistics($this->user->id, 'invoice', 'count', 'monthly');
        
        $this->assertNotNull($stats);
        $this->assertEquals(2, $stats['current_usage']); // Changed from current_count
        $this->assertEquals('monthly', $stats['period_type']);
        $this->assertEquals('invoice', $stats['entity_type']);
    }

    #[Test]
    public function get_usage_statistics_returns_data_for_nonexistent_limit(): void
    {
        $stats = $this->service->getUsageStatistics($this->user->id, 'nonexistent', 'count', 'monthly');
        
        // Service always returns data structure, even for nonexistent limits
        $this->assertNotNull($stats);
        $this->assertEquals(0, $stats['current_usage']);
        $this->assertEquals(0, $stats['limit']);
        $this->assertFalse($stats['can_create']);
    }

    #[Test]
    public function reset_usage_clears_current_usage(): void
    {
        // Record some usage
        $this->service->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1);

        // Reset usage
        $result = $this->service->resetUsage($this->user->id, 'invoice', 'count', 'monthly');
        
        $this->assertTrue($result);
        
        // Verify usage is cleared
        $stats = $this->service->getUsageStatistics($this->user->id, 'invoice', 'count', 'monthly');
        $this->assertEquals(0, $stats['current_usage']); // Changed from current_count
    }

    #[Test]
    public function check_limit_works_with_different_period_types(): void
    {
        // Create daily limit
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'daily',
            'limit_value' => 5,
            'description' => 'Daily invoice creation limit',
            'is_active' => true
        ]);

        $dailyResult = $this->service->checkLimit($this->user->id, 'invoice', 'count', 'daily');
        $monthlyResult = $this->service->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        
        $this->assertTrue($dailyResult['allowed']);
        $this->assertEquals(5, $dailyResult['limit']);
        
        $this->assertTrue($monthlyResult['allowed']);
        $this->assertEquals(10, $monthlyResult['limit']);
    }

    #[Test]
    public function service_handles_multiple_permissions_with_different_limits(): void
    {
        // Create another permission with higher limit
        Permission::firstOrCreate([
            'name' => 'frontend.can_create_edit_invoice_premium',
            'guard_name' => 'web'
        ]);

        $this->user->givePermissionTo('frontend.can_create_edit_invoice_premium');

        // Create higher limit for premium permission
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice_premium',
            'entity_type' => 'invoice',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 50,
            'description' => 'Premium monthly invoice limit',
            'is_active' => true
        ]);

        $result = $this->service->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        
        // Should return the highest limit from all permissions
        $this->assertTrue($result['allowed']);
        $this->assertEquals(50, $result['limit']);
    }
}
