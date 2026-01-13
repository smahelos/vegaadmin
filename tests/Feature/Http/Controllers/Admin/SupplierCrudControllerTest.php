<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Supplier;
use App\Models\EntityLimit;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Tests\Traits\CreatesAdminTestEnvironment;
class SupplierCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();

        $this->limitService = app(UniversalLimitService::class);
    }

    #[Test]
    public function supplier_creation_records_entity_usage(): void
    {
        // Set a limit for suppliers
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_supplier',
            'entity_type' => 'supplier',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit($this->adminUser->id, 'supplier', 'count', 'monthly');
        $initialUsage = $initialCheck['current_usage'] ?? 0;
        
        // Simulate supplier creation by directly calling recordUsage
        $supplier = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $result = $this->limitService->recordUsage($this->adminUser->id, 'supplier', 'count', 'monthly', 'backpack');
        $this->assertTrue($result, 'recordUsage should return true when successful');
        
        // Check new usage
        $newCheck = $this->limitService->checkLimit($this->adminUser->id, 'supplier', 'count', 'monthly');
        $newUsage = $newCheck['current_usage'] ?? 0;
        
        $this->assertEquals($initialUsage + 1, $newUsage, 'Usage should increase by 1 after recording');
        
        // Verify supplier was created
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    #[Test]
    public function supplier_creation_respects_entity_limits(): void
    {
        // Set a strict limit for suppliers
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_supplier',
            'entity_type' => 'supplier',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        // First supplier creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit($this->adminUser->id, 'supplier', 'count', 'monthly');
        $this->assertTrue($checkBeforeFirst['allowed']);

        $supplier1 = Supplier::factory()->create(['user_id' => $this->adminUser->id]);
        $this->limitService->recordUsage($this->adminUser->id, 'supplier', 'count', 'monthly', 'backpack');

        // Second supplier creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit($this->adminUser->id, 'supplier', 'count', 'monthly');
        $this->assertFalse($checkBeforeSecond['allowed']);
        $this->assertEquals('limit_exceeded', $checkBeforeSecond['reason']);
        
        // Verify first supplier was created
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier1->id,
            'user_id' => $this->adminUser->id,
        ]);
    }
}
