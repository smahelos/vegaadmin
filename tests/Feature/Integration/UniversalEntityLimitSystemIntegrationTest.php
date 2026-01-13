<?php

namespace Tests\Feature\Integration;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Http\Requests\ClientRequest;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\SupplierRequest;
use App\Models\Client;
use App\Models\EntityLimit;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class UniversalEntityLimitSystemIntegrationTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

    protected User $adminUser;
    protected User $user;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up frontend test environment with roles and permissions
        $this->setUpFrontendTestEnvironment();
        
        // Give frontend permissions to user (instead of backpack permissions)
        $permissions = ['frontend.can_create_edit_invoice', 'frontend.can_create_edit_client', 'frontend.can_create_edit_supplier'];
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();
            if ($permission) {
                $this->user->givePermissionTo($permission);
            }
        }

        $this->limitService = app(UniversalLimitService::class);
    }

    #[Test]
    public function complete_client_workflow_respects_limits(): void
    {
        $this->actingAs($this->user); // web guard pro frontend

        // Set up a strict monthly client limit
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_client',
            'entity_type' => 'client',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 2,
            'description' => 'Monthly client limit for testing',
            'is_active' => true,
        ]);

        // First client request should be allowed
        $request1 = new ClientRequest();
        $request1->setMethod('POST');
        $request1->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->assertTrue($request1->authorize());

        // Record the creation
        $this->limitService->recordUsage($this->user->id, 'client', 'count', 'monthly');

        // Second client request should be allowed
        $request2 = new ClientRequest();
        $request2->setMethod('POST');
        $request2->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->assertTrue($request2->authorize());

        // Record the second creation
        $this->limitService->recordUsage($this->user->id, 'client', 'count', 'monthly');

        // Third client request should fail due to limit (already at limit of 2)
        $request3 = new ClientRequest();
        $request3->setMethod('POST');
        $request3->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $request3->authorize();
    }

    #[Test]
    public function different_entities_have_independent_limits(): void
    {
        $this->actingAs($this->user);

        // Set up different limits for different entities (not invoices - they're unlimited)
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_client',
            'entity_type' => 'client',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 1,
            'description' => 'Monthly client limit for testing',
            'is_active' => true,
        ]);

        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_supplier',
            'entity_type' => 'supplier',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 3,
            'description' => 'Monthly supplier limit for testing',
            'is_active' => true,
        ]);

    // Create one client (at limit) - observer will record usage automatically
    Client::factory()->create(['user_id' => $this->user->id]);
        
        $clientRequest = new ClientRequest();
        $clientRequest->setMethod('POST');
        $clientRequest->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }];
        });
        
        // Should not be able to create another client (at limit)
        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $clientRequest->authorize();
    }

    #[Test]
    public function updates_bypass_limit_checks(): void
    {
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // New creation should fail
        $newRequest = new InvoiceRequest();
        $newRequest->setMethod('POST');
        $newRequest->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $newRequest->authorize();
    }

    #[Test]
    public function updates_bypass_limit_checks_update_allowed(): void
    {
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // But update should succeed even with 0 limit
        $updateRequest = new InvoiceRequest();
        $updateRequest->setMethod('PUT');
        $updateRequest->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return 'existing-invoice-id'; }]; // Update
        });

        $this->assertTrue($updateRequest->authorize());
    }

    #[Test]
    public function unlimited_access_bypasses_all_limits(): void
    {
        // Create a user with unlimited access
        $unlimitedPermission = Permission::firstOrCreate(['name' => 'can_create_invoice_unlimited', 'guard_name' => 'backpack']);
        $this->user->givePermissionTo($unlimitedPermission);
        $this->actingAs($this->user);

        // Set up a strict limit (0 new invoices allowed)
        EntityLimit::factory()
            ->forEntity('invoice')
            ->countLimit(0)
            ->monthly()
            ->active()
            ->create();

        // Should succeed despite limit due to unlimited access
        $request = new InvoiceRequest();
        $request->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function system_works_without_any_limits_configured(): void
    {
        $this->actingAs($this->user);

        // No limits configured - should always allow
        $request = new InvoiceRequest();
        $request->setRouteResolver(function() {
            return (object) ['parameter' => function($name) { return null; }]; // New creation
        });

        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function multiple_request_classes_integrate_correctly(): void
    {
        $this->actingAs($this->user);

        // Test each request type has correct entity type
        $invoiceRequest = new InvoiceRequest();
        $reflection = new \ReflectionClass($invoiceRequest);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('invoice', $method->invoke($invoiceRequest));

        $clientRequest = new ClientRequest();
        $reflection = new \ReflectionClass($clientRequest);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('client', $method->invoke($clientRequest));

        $supplierRequest = new SupplierRequest();
        $reflection = new \ReflectionClass($supplierRequest);
        $method = $reflection->getMethod('getEntityType');
        $method->setAccessible(true);
        $this->assertEquals('supplier', $method->invoke($supplierRequest));

        // Test they all extend BaseEntityRequest (frontend version)
        $this->assertInstanceOf(\App\Http\Requests\BaseEntityRequest::class, $invoiceRequest);
        $this->assertInstanceOf(\App\Http\Requests\BaseEntityRequest::class, $clientRequest);
        $this->assertInstanceOf(\App\Http\Requests\BaseEntityRequest::class, $supplierRequest);
    }

    #[Test]
    public function usage_statistics_work_correctly(): void
    {
        $this->actingAs($this->user);

        // Create a limit for clients with correct permission_name
        EntityLimit::factory()->create([
            'permission_name' => 'frontend.can_create_edit_client',
            'entity_type' => 'client',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 10,
            'description' => 'Monthly client limit for statistics testing',
            'is_active' => true,
        ]);

    // Create actual entities (observer records usage)
    Client::factory()->create(['user_id' => $this->user->id]);
    Client::factory()->create(['user_id' => $this->user->id]);

        // Check statistics
        $stats = $this->limitService->getUsageStatistics($this->user->id, 'client', 'count', 'monthly');

        $this->assertNotNull($stats);
        $this->assertEquals(2, $stats['current_usage']);
        $this->assertEquals(10, $stats['limit']);
        $this->assertEquals('monthly', $stats['period_type']);
    }
}
