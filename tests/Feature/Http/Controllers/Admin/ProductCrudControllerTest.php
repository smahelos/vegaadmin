<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ProductCrudControllerTest extends TestCase
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
        $permission = Permission::where('name', 'can_create_edit_product')
                               ->where('guard_name', 'backpack')
                               ->first();

        $this->regularUser->givePermissionTo($permission);

        $this->limitService = app(UniversalLimitService::class);
    }

    #[Test]
    public function product_creation_records_entity_usage(): void
    {
        // Arrange
        $this->actingAs($this->regularUser, 'backpack');
        
        // Create entity limit for products
        EntityLimit::factory()->create([
            'entity_type' => 'product',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_product',
            'is_active' => true,
        ]);

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit($this->regularUser->id, 'product', 'count', 'monthly');
        $initialUsage = $initialCheck['current_usage'] ?? 0;

        $productData = [
            'name' => 'Test Product ' . uniqid(),
            'description' => 'Test Description',
            'price' => 99.99,
            'user_id' => $this->regularUser->id,
        ];

        $product = Product::factory()->create($productData);
        $result = $this->limitService->recordUsage($this->regularUser->id, 'product', 'count', 'monthly', 'backpack');
        $this->assertTrue($result, 'recordUsage should return true when successful');

        // Check usage after creation
        $newCheck = $this->limitService->checkLimit($this->regularUser->id, 'product', 'count', 'monthly');
        $newUsage = $newCheck['current_usage'] ?? 0;
        
        $this->assertEquals($initialUsage + 1, $newUsage);

        // Verify product was created
        $this->assertDatabaseHas('products', [
            'name' => $productData['name'],
            'user_id' => $this->regularUser->id,
        ]);
    }

    #[Test]
    public function product_creation_respects_entity_limits(): void
    {
        // Set a strict limit for products
        EntityLimit::factory()->create([
            'entity_type' => 'product',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_product',
            'is_active' => true,
        ]);

        // First product creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit($this->regularUser->id, 'product', 'count', 'monthly');
        $this->assertTrue($checkBeforeFirst['allowed']);

        $product1 = Product::factory()->create(['user_id' => $this->regularUser->id]);
        $result1 = $this->limitService->recordUsage($this->regularUser->id, 'product', 'count', 'monthly', 'backpack');
        $this->assertTrue($result1, 'First recordUsage should succeed');

        // Second product creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit($this->regularUser->id, 'product', 'count', 'monthly');
        $this->assertFalse($checkBeforeSecond['allowed']);
        $this->assertEquals('limit_exceeded', $checkBeforeSecond['reason']);
        
        // Verify first product was created
        $this->assertDatabaseHas('products', [
            'id' => $product1->id,
            'user_id' => $this->regularUser->id,
        ]);
    }
}
