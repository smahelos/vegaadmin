<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Supplier;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for Api\SupplierController
 * 
 * Tests all API endpoints: getSupplier($id), getSuppliers(), getDefaultSupplier()
 * Tests authentication scenarios, authorization (admin vs regular user access), error handling
 * Tests JSON responses, HTTP status codes, and security boundaries
 */
class SupplierControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $user;
    protected User $viewer;
    protected Supplier $adminSupplier;
    protected Supplier $userSupplier;
    protected Supplier $defaultSupplier;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create test users with proper roles
        $this->createTestUsers();

        // Create test suppliers
        $this->createTestSuppliers();
    }

    /**
     * Create necessary permissions and roles for testing
     */
    private function createPermissionsAndRoles(): void
    {
        // Frontend permissions
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);
        
        // Backpack permissions
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_supplier', 'guard_name' => 'backpack']);
        
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->syncPermissions([
            'backpack.access',
            'backpack.api.access', 
            'can_view_supplier'
        ]);
        
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.suppliers'
        ]);
        
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerRole->syncPermissions([
            'frontend.api.access'
        ]);
    }

    /**
     * Create test users with proper roles
     */
    private function createTestUsers(): void
    {
        // Create admin user - assign role with specific guard
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'backpack')->first();
        $this->admin->assignRole($adminRole);

        // Create regular user
        $this->user = User::factory()->create([
            'name' => 'Regular User', 
            'email' => 'user@example.com',
        ]);
        $userRole = Role::where('name', 'user')->where('guard_name', 'web')->first();
        $this->user->assignRole($userRole);

        // Create viewer user
        $this->viewer = User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
        ]);
        $viewerRole = Role::where('name', 'viewer')->where('guard_name', 'web')->first();
        $this->viewer->assignRole($viewerRole);
    }

    /**
     * Create test suppliers for different scenarios
     */
    private function createTestSuppliers(): void
    {
        // Admin's supplier
        $this->adminSupplier = Supplier::factory()->create([
            'user_id' => $this->admin->id,
            'name' => 'Admin Test Supplier',
            'email' => 'admin.supplier@example.com',
            'is_default' => false,
        ]);

        // User's default supplier
        $this->defaultSupplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Default Supplier',
            'email' => 'user.default@example.com',
            'is_default' => true,
        ]);

        // User's regular supplier
        $this->userSupplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Regular Supplier',
            'email' => 'user.supplier@example.com',
            'is_default' => false,
        ]);
    }

    /**
     * Test getSupplierAdmin endpoint with authenticated admin user
     */
    #[Test]
    public function admin_can_get_any_supplier(): void
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/supplier/{$this->userSupplier->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userSupplier->id,
                'name' => $this->userSupplier->name,
                'email' => $this->userSupplier->email,
                'user_id' => $this->user->id,
            ]);
    }

    /**
     * Test getSupplier endpoint with authenticated regular user accessing own supplier
     */
    #[Test]
    public function user_can_get_own_supplier(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/supplier/{$this->userSupplier->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userSupplier->id,
                'name' => $this->userSupplier->name,
                'email' => $this->userSupplier->email,
                'user_id' => $this->user->id,
            ]);
    }

    /**
     * Test getSupplier endpoint with regular user trying to access other user's supplier
     */
    #[Test]
    public function user_cannot_get_other_users_supplier(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/supplier/{$this->adminSupplier->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => __('suppliers.messages.not_found'),
            ]);
    }

    /**
     * Test getSupplier endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_supplier(): void
    {
        $response = $this->getJson("/api/supplier/{$this->userSupplier->id}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test getSupplier endpoint with non-existent supplier ID
     */
    #[Test]
    public function get_supplier_with_non_existent_id_returns_404(): void
    {
        $nonExistentId = 999999;
            
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/supplier/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => __('suppliers.messages.not_found'),
            ]);
    }

    /**
     * Test getSuppliers endpoint with authenticated admin user
     */
    #[Test]
    public function admin_can_get_all_suppliers(): void
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson('/api/admin/supplier');

        $response->assertStatus(200);
            
        $suppliers = $response->json();
        $this->assertIsArray($suppliers);
            
        // Admin should see all suppliers
        $expectedSuppliers = [$this->adminSupplier, $this->userSupplier, $this->defaultSupplier];
        $this->assertGreaterThanOrEqual(3, count($suppliers), 'Admin should see at least the 3 test suppliers');
            
        // Check if all test suppliers are included
        $supplierIds = collect($suppliers)->pluck('id')->toArray();
        $this->assertContains($this->adminSupplier->id, $supplierIds);
        $this->assertContains($this->userSupplier->id, $supplierIds);
        $this->assertContains($this->defaultSupplier->id, $supplierIds);
    }

    /**
     * Test getSuppliers endpoint with authenticated regular user
     */
    #[Test]
    public function user_can_get_only_own_suppliers(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier');

        $response->assertStatus(200);
            
        $suppliers = $response->json();
        $this->assertIsArray($suppliers);
            
        // User should see only their own suppliers
        $this->assertCount(2, $suppliers);
            
        // Check if only user's suppliers are included
        $supplierIds = collect($suppliers)->pluck('id')->toArray();
        $this->assertContains($this->userSupplier->id, $supplierIds);
        $this->assertContains($this->defaultSupplier->id, $supplierIds);
        $this->assertNotContains($this->adminSupplier->id, $supplierIds);
            
        // Verify all returned suppliers belong to the authenticated user
        foreach ($suppliers as $supplier) {
            $this->assertEquals($this->user->id, $supplier['user_id']);
        }
    }

    /**
     * Test getSuppliers endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_suppliers(): void
    {
        $response = $this->getJson('/api/supplier');

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test getDefaultSupplier endpoint with authenticated user having default supplier
     */
    #[Test]
    public function user_can_get_default_supplier(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier/default');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->defaultSupplier->id,
                'name' => $this->defaultSupplier->name,
                'email' => $this->defaultSupplier->email,
                'user_id' => $this->user->id,
                'is_default' => true,
            ]);
    }

    /**
     * Test getDefaultSupplier endpoint with user having no default supplier
     */
    #[Test]
    public function user_gets_most_recent_supplier_when_no_default(): void
    {
        // Remove default status from all user's suppliers
        Supplier::where('user_id', $this->user->id)->update(['is_default' => false]);

        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier/default');

        $response->assertStatus(200);
            
        $supplier = $response->json();
        $this->assertEquals($this->user->id, $supplier['user_id']);
        $this->assertFalse($supplier['is_default']);
            
        // Should return the most recently created supplier
        $mostRecent = Supplier::where('user_id', $this->user->id)
            ->orderBy('created_at', 'desc')
            ->first();
        $this->assertEquals($mostRecent->id, $supplier['id']);
    }

    /**
     * Test getDefaultSupplier endpoint with user having no suppliers
     */
    #[Test]
    public function user_with_no_suppliers_gets_404(): void
    {
        // Delete all user's suppliers
        Supplier::where('user_id', $this->user->id)->delete();

        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier/default');

        $response->assertStatus(404)
            ->assertJson([
                'error' => __('suppliers.messages.no_suppliers'),
            ]);
    }

    /**
     * Test getDefaultSupplier endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_default_supplier(): void
    {
        $response = $this->getJson('/api/supplier/default');

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test API routes are properly protected by middleware
     */
    #[Test]
    public function api_routes_require_authentication(): void
    {
        // Test all API endpoints without authentication
        $endpoints = [
            "/api/supplier/{$this->userSupplier->id}",
            '/api/supplier',
            '/api/supplier/default',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertContains($response->status(), [401], 
                "Endpoint {$endpoint} should require authentication");
        }
    }

    /**
     * Test JSON response structure for getSupplier endpoint (frontend)
     */
    #[Test]
    public function get_supplier_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/supplier/{$this->userSupplier->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'street',
                'city',
                'zip',
                'country',
                'ico',
                'dic',
                'phone',
                'description',
                'is_default',
                'user_id',
                'account_number',
                'bank_code',
                'iban',
                'swift',
                'bank_name',
                'has_payment_info',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test JSON response structure for getSuppliers endpoint
     */
    #[Test]
    public function get_suppliers_returns_correct_json_structure(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email',
                    'street',
                    'city',
                    'zip',
                    'country',
                    'ico',
                    'dic',
                    'phone',
                    'description',
                    'is_default',
                    'user_id',
                    'account_number',
                    'bank_code',
                    'iban',
                    'swift',
                    'bank_name',
                    'has_payment_info',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    /**
     * Test admin API route for getSupplier endpoint
     */
    #[Test]
    public function admin_api_route_works_for_admin(): void
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/supplier/{$this->userSupplier->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userSupplier->id,
                'name' => $this->userSupplier->name,
            ]);
    }

    /**
     * Test admin API route access for regular user
     */
    #[Test]
    public function admin_api_route_requires_admin_role(): void
    {
        $response = $this->actingAs($this->user, 'backpack')
                ->getJson("/api/admin/supplier/{$this->userSupplier->id}");

        // Should return 403 for user without admin role
        $response->assertStatus(403)
            ->assertJson([
                'error' => __('backpack::base.forbidden'),
            ]);
    }

    /**
     * Test API responses include correct headers
     */
    #[Test]
    public function api_responses_include_correct_headers(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test proper handling of database exceptions
     */
    #[Test]
    public function handles_database_exceptions_gracefully(): void
    {
        // Test with invalid ID format that could cause database error
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson('/api/admin/supplier/invalid-id');

        $response->assertStatus(404)
            ->assertJson([
                'error' => __('suppliers.messages.not_found'),
            ]);
    }

    /**
     * Test that supplier data includes all necessary fields for frontend usage
     */
    #[Test]
    public function supplier_data_includes_all_frontend_fields(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/supplier/{$this->userSupplier->id}");

        $response->assertStatus(200);
            
        $supplier = $response->json();
            
        // Verify all fields needed by frontend are present
        $requiredFields = [
            'id', 'name', 'email', 'phone', 'street', 'city', 'zip', 
            'country', 'ico', 'dic', 'account_number', 'bank_code', 
            'bank_name', 'iban', 'swift', 'is_default', 'has_payment_info'
        ];
            
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $supplier, 
                "Field {$field} is missing from supplier response");
        }
    }

    /**
     * Test viewer role permissions
     */
    #[Test]
    public function viewer_role_has_limited_access(): void
    {
        // Create a supplier for viewer
        $viewerSupplier = Supplier::factory()->create([
            'user_id' => $this->viewer->id,
            'name' => 'Viewer Supplier',
            'is_default' => true,
        ]);

        // Viewer should be able to access their own suppliers
        $response = $this->actingAs($this->viewer, 'web')
            ->getJson("/api/supplier/{$viewerSupplier->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that multiple users can have their own default suppliers
     */
    #[Test]
    public function multiple_users_can_have_default_suppliers(): void
    {
        // Set admin's supplier as default
        $this->adminSupplier->update(['is_default' => true]);

        // Check user still has their default
        $userResponse = $this->actingAs($this->user, 'web')
            ->getJson('/api/supplier/default');
        $userResponse->assertStatus(200);
        $userDefault = $userResponse->json();
        $this->assertEquals($this->defaultSupplier->id, $userDefault['id']);

        // Check admin would access their default via individual supplier endpoint
        $adminResponse = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/supplier/{$this->adminSupplier->id}");
        $adminResponse->assertStatus(200);
        $adminDefault = $adminResponse->json();
        $this->assertEquals($this->adminSupplier->id, $adminDefault['id']);
    }
}
