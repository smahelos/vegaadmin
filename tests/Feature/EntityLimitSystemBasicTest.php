<?php

namespace Tests\Feature;

use App\Models\EntityLimit;
use App\Models\User;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EntityLimitSystemBasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $backendUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Get roles and permissions created by TestingDatabaseSeeder
        //$adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $frontendUserRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $backendUserRole = Role::where('name', 'backend_user')->where('guard_name', 'backpack')->first();
        $permission = Permission::where('name', 'can_create_edit_invoice')
            ->where('guard_name', 'backpack')
            ->first();
        $permission2 = Permission::where('name', 'can_create_edit_client')
            ->where('guard_name', 'backpack')
            ->first();
        $permission3 = Permission::where('name', 'can_create_edit_supplier')
            ->where('guard_name', 'backpack')
            ->first();
        
        // Create admin user with admin role (has all permissions)
        $this->backendUser = User::factory()->create();
        $this->backendUser->assignRole($backendUserRole);
        $this->backendUser->givePermissionTo($permission);
        $this->backendUser->givePermissionTo($permission2);
        $this->backendUser->givePermissionTo($permission3);

        // Create regular user with limited role
        $this->user = User::factory()->create();
        $this->user->assignRole($frontendUserRole);
    }

    #[Test]
    public function entity_limit_model_can_be_created(): void
    {
        $this->actingAs($this->user, 'web');

        // Create permission for testing
        Permission::firstOrCreate([
            'name' => 'test_invoice_limit',
            'guard_name' => 'web'
        ]);

        $limit = EntityLimit::factory()->create([
            'permission_name' => 'test_invoice_limit',
            'entity_type' => 'invoice',
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'limit_value' => 10,
            'is_active' => true,
        ]);

        $this->assertNotNull($limit->id);
        $this->assertEquals('test_invoice_limit', $limit->permission_name);
        $this->assertEquals('invoice', $limit->entity_type);
        $this->assertEquals(10, $limit->limit_value);
    }

    #[Test]
    public function universal_limit_service_can_check_limits(): void
    {
        $this->actingAs($this->user, 'web');
        
        // Create permission for testing
        Permission::firstOrCreate([
            'name' => 'monthly_invoice_limit',
            'guard_name' => 'web'
        ]);
        
        // Give user the permission
        $this->user->givePermissionTo('monthly_invoice_limit');

        // Create a limit
        EntityLimit::factory()->create([
            'permission_name' => 'monthly_invoice_limit',
            'entity_type' => 'invoice',
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'limit_value' => 5,
            'is_active' => true,
        ]);

        $limitService = app(UniversalLimitService::class);

        // Should be allowed initially
        $check = $limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly', 1);
        $this->assertTrue($check['allowed']);

        // Record usage
        $this->assertTrue($limitService->recordUsage($this->user->id, 'invoice', 'count', 'monthly', 'web', 1));

        // Should still be allowed
        $check = $limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly', 1);
        $this->assertTrue($check['allowed']);
    }

    #[Test]
    public function request_authorization_structure_works(): void
    {
        $this->actingAs($this->backendUser, 'backpack');

        // Test InvoiceRequest instantiation
        $request = new \App\Http\Requests\Admin\InvoiceRequest();
        
        // Test that it extends BaseEntityRequest
        $this->assertInstanceOf(\App\Http\Requests\Admin\BaseEntityRequest::class, $request);

        // Test entity type method
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('invoice', $method->invoke($request));

        // Test permission method
        $method = $reflection->getMethod('getRequiredPermission');
        $method->setAccessible(true);
        $this->assertEquals('can_create_edit_invoice', $method->invoke($request));
    }

    #[Test]
    public function client_and_supplier_requests_work_correctly(): void
    {
        $this->actingAs($this->backendUser, 'backpack');

        // Test ClientRequest
        $clientRequest = new \App\Http\Requests\Admin\ClientRequest();
        $this->assertInstanceOf(\App\Http\Requests\Admin\BaseEntityRequest::class, $clientRequest);

        $reflection = new \ReflectionClass($clientRequest);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('client', $method->invoke($clientRequest));

        // Test SupplierRequest
        $supplierRequest = new \App\Http\Requests\Admin\SupplierRequest();
        $this->assertInstanceOf(\App\Http\Requests\Admin\BaseEntityRequest::class, $supplierRequest);

        $reflection = new \ReflectionClass($supplierRequest);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('supplier', $method->invoke($supplierRequest));
    }

    #[Test]
    public function translation_keys_exist(): void
    {
        // Set locale to English for testing
        app()->setLocale('en');

        // Test that our translation keys exist
        $this->assertNotEmpty(__('general.entity_limits.entities.invoice'));
        $this->assertNotEmpty(__('general.entity_limits.periods.monthly'));
        $this->assertNotEmpty(__('general.entity_limits.limit_types.count'));

        // Test Czech translations exist
        app()->setLocale('cs');
        $this->assertNotEmpty(__('general.entity_limits.entities.invoice'));
        $this->assertNotEmpty(__('general.entity_limits.periods.monthly'));
    }

    #[Test]
    public function entity_limit_factory_works(): void
    {
        // Test the factory can create instances
        $limit = EntityLimit::factory()->make();
        $this->assertNotNull($limit->permission_name);
        $this->assertNotNull($limit->entity_type);

        // Test specific factory states
        $countLimit = EntityLimit::factory()->countLimit(15)->make();
        $this->assertEquals('count', $countLimit->metric_type);
        $this->assertEquals(15, $countLimit->limit_value);

        $invoiceLimit = EntityLimit::factory()->forEntity('invoice')->make();
        $this->assertEquals('invoice', $invoiceLimit->entity_type);
    }
}
