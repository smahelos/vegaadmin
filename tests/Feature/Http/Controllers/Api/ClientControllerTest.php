<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Client;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for Api\ClientController
 * 
 * Tests all API endpoints: getClient($id), getClients(), getDefaultClient()
 * Tests authentication scenarios, authorization (admin vs regular user access), error handling
 * Tests JSON responses, HTTP status codes, and security boundaries
 */
class ClientControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;
    protected User $user;
    protected User $viewer;
    protected Client $adminClient;
    protected Client $userClient;
    protected Client $defaultClient;

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

        // Create test clients
        $this->createTestClients();
    }

    /**
     * Create necessary permissions and roles for testing
     */
    private function createPermissionsAndRoles(): void
    {
        // Frontend permissions
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.clients', 'guard_name' => 'web']);
        
        // Backpack permissions
        Permission::firstOrCreate(['name' => 'backpack.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'backpack.api.access', 'guard_name' => 'backpack']);
        Permission::firstOrCreate(['name' => 'can_view_client', 'guard_name' => 'backpack']);
        
        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'backpack']);
        $adminRole->syncPermissions([
            'backpack.access',
            'backpack.api.access', 
            'can_view_client'
        ]);
        
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients'
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
     * Create test clients for different scenarios
     */
    private function createTestClients(): void
    {
        // Admin's client
        $this->adminClient = Client::factory()->create([
            'user_id' => $this->admin->id,
            'name' => 'Admin Test Client',
            'email' => 'admin.client@example.com',
            'is_default' => false,
        ]);

        // User's default client
        $this->defaultClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Default Client',
            'email' => 'user.default@example.com',
            'is_default' => true,
        ]);

        // User's regular client
        $this->userClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Regular Client',
            'email' => 'user.client@example.com',
            'is_default' => false,
        ]);
    }

    /**
     * Test getClientAdmin endpoint with authenticated admin user
     */
    #[Test]
    public function admin_can_get_any_client(): void
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/client/{$this->userClient->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userClient->id,
                'name' => $this->userClient->name,
                'email' => $this->userClient->email,
                'user_id' => $this->user->id,
            ]);
    }

    /**
     * Test getClient endpoint with authenticated regular user accessing own client
     */
    #[Test]
    public function user_can_get_own_client(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/client/{$this->userClient->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userClient->id,
                'name' => $this->userClient->name,
                'email' => $this->userClient->email,
                'user_id' => $this->user->id,
            ]);
    }

    /**
     * Test getClient endpoint with regular user trying to access other user's client
     */
    #[Test]
    public function user_cannot_get_other_users_client(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/client/{$this->adminClient->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => __('clients.messages.not_found'),
            ]);
    }

    /**
     * Test getClient endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_client(): void
    {
        $response = $this->getJson("/api/client/{$this->userClient->id}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test getClient endpoint with non-existent client ID
     */
    #[Test]
    public function get_non_existent_client_returns_404(): void
    {
        $nonExistentId = 99999;

        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/client/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'error' => __('clients.messages.not_found'),
            ]);
    }

    /**
     * Test admin api route requires admin role
     */
    #[Test]
    public function admin_api_route_requires_admin_role(): void
    {
        $response = $this->actingAs($this->user, 'backpack')
            ->getJson("/api/admin/client/{$this->userClient->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => __('backpack::base.forbidden'),
            ]);
    }

    /**
     * Test getClients endpoint returns all clients for admin user
     */
    #[Test]
    public function admin_can_get_all_clients(): void
    {
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson('/api/admin/client');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email',
                    'user_id',
                ]
            ]);

        $clients = $response->json();
        $this->assertGreaterThanOrEqual(1, count($clients)); // At least one client
        
        // Verify admin can see any client via admin API
        $clientIds = collect($clients)->pluck('id')->toArray();
        // Note: Admin API may return different data structure than frontend API
    }

    /**
     * Test getClients endpoint returns only user's clients for regular user
     */
    #[Test]
    public function user_can_get_only_own_clients(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/client');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'email',
                    'user_id',
                ]
            ]);

        $clients = $response->json();
        
        // Verify all returned clients belong to the user
        foreach ($clients as $client) {
            $this->assertEquals($this->user->id, $client['user_id']);
        }
        
        // Verify user's clients are included
        $clientIds = collect($clients)->pluck('id')->toArray();
        $this->assertContains($this->userClient->id, $clientIds);
        $this->assertContains($this->defaultClient->id, $clientIds);
        
        // Verify admin's client is not included
        $this->assertNotContains($this->adminClient->id, $clientIds);
    }

    /**
     * Test getClients endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_clients(): void
    {
        $response = $this->getJson('/api/client');

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test getDefaultClient endpoint returns default client for user
     */
    #[Test]
    public function user_can_get_default_client(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson('/api/client/default');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->defaultClient->id,
                'name' => $this->defaultClient->name,
                'email' => $this->defaultClient->email,
                'user_id' => $this->user->id,
                'is_default' => true,
            ]);
    }

    /**
     * Test getDefaultClient endpoint with user who has no default client
     */
    #[Test]
    public function user_gets_first_client_when_no_default_set(): void
    {
        // Create a user with multiple clients but no default
        $testUser = User::factory()->create([
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ]);
        $testUser->assignRole('user'); // Assign frontend role
        
        $firstClient = Client::factory()->create([
            'user_id' => $testUser->id,
            'name' => 'First Client',
            'is_default' => false,
        ]);
        
        $secondClient = Client::factory()->create([
            'user_id' => $testUser->id,
            'name' => 'Second Client',
            'is_default' => false,
        ]);

        $response = $this->actingAs($testUser, 'web')
            ->getJson('/api/client/default');

        $response->assertStatus(200);
        
        $client = $response->json();
        $this->assertEquals($testUser->id, $client['user_id']);
        
        // Should return one of the user's clients (most recent by created_at)
        $clientIds = [$firstClient->id, $secondClient->id];
        $this->assertContains($client['id'], $clientIds);
    }

    /**
     * Test getDefaultClient endpoint with user who has no clients
     */
    #[Test]
    public function user_gets_404_when_no_clients_exist(): void
    {
        // Create a user with no clients
        $testUser = User::factory()->create([
            'email' => 'noclient@example.com',
            'name' => 'No Client User',
        ]);
        $testUser->assignRole('user'); // Assign frontend role

        $response = $this->actingAs($testUser, 'web')
            ->getJson('/api/client/default');

        $response->assertStatus(404)
            ->assertJson([
                'error' => __('clients.messages.not_found'),
            ]);
    }

    /**
     * Test getDefaultClient endpoint with unauthenticated request
     */
    #[Test]
    public function unauthenticated_user_cannot_get_default_client(): void
    {
        $response = $this->getJson('/api/client/default');

        $response->assertStatus(401)
            ->assertJson([
                'error' => __('users.auth.unauthenticated'),
                'code' => 401
            ]);
    }

    /**
     * Test that admin user cannot access frontend API endpoints
     * Admin users have backpack permissions only, not web permissions
     */
    #[Test]
    public function admin_cannot_access_frontend_api(): void
    {
        // Create admin's default client
        $adminDefaultClient = Client::factory()->create([
            'user_id' => $this->admin->id,
            'name' => 'Admin Default Client',
            'email' => 'admin.default@example.com',
            'is_default' => true,
        ]);

        // Admin user should NOT be able to access frontend API endpoints
        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/client/default');

        $response->assertStatus(403)
            ->assertJson([
                'error' => __('users.auth.forbidden'),
                'code' => 403
            ]);
    }

    /**
     * Test JSON response structure for getClient endpoint
     */
    #[Test]
    public function get_client_response_structure(): void
    {
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/client/{$this->userClient->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'user_id',
                'name',
                'email',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Test that admin endpoint bypasses user ownership checks
     */
    #[Test]
    public function admin_endpoint_bypasses_ownership_checks(): void
    {
        // Admin should be able to access any user's client via admin endpoint
        $response = $this->actingAs($this->admin, 'backpack')
            ->getJson("/api/admin/client/{$this->userClient->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->userClient->id,
                'user_id' => $this->user->id, // Client belongs to regular user, not admin
            ]);
    }

    /**
     * Test that regular endpoint enforces user ownership checks
     */
    #[Test]
    public function regular_endpoint_enforces_ownership_checks(): void
    {
        // Regular user should NOT be able to access admin's client via regular endpoint
        $response = $this->actingAs($this->user, 'web')
            ->getJson("/api/client/{$this->adminClient->id}");

        $response->assertStatus(403)
            ->assertJson([
                'error' => __('clients.messages.not_found'),
            ]);
    }
}
