<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\ClientController;
use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\User;
use App\Models\Invoice;
use App\Services\CountryService;
use App\Services\LocaleService;
use App\Repositories\ClientRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for Frontend\ClientController
 * 
 * Tests all frontend client management endpoints: index, create, store, show, edit, update, destroy, setDefault
 * Tests authentication scenarios, authorization (user ownership checks), validation, error handling
 * Tests view rendering, form processing, and security boundaries for client management
 */
class ClientControllerFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected string $validEmail;
    protected array $validClientData;
    protected string $defaultLocale = 'cs';

    /**
     * Helper method to generate routes with locale parameter
     *
     * @param string $routeName
     * @param mixed $parameters
     * @return string
     */
    private function localizedRoute(string $routeName, $parameters = []): string
    {
        if (is_numeric($parameters) || is_string($parameters)) {
            // Single parameter (like ID)
            return route($routeName, ['locale' => $this->defaultLocale, 'id' => $parameters]);
        }
        
        if (is_array($parameters)) {
            // Multiple parameters
            return route($routeName, array_merge(['locale' => $this->defaultLocale], $parameters));
        }
        
        // No additional parameters
        return route($routeName, ['locale' => $this->defaultLocale]);
    }

    /**
     * Set up the test environment.
     * Creates permissions, roles, test user, and valid test data for client operations.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and roles
        $this->createPermissionsAndRoles();
        
        // Create test user with proper roles
        $this->createTestUser();

        // Set up valid client data for testing
        $this->setupValidClientData();
    }

    /**
     * Setup valid client data for testing
     */
    private function setupValidClientData(): void
    {
        $this->validClientData = [
            'name' => 'Test Client Company',
            'email' => 'client-' . uniqid() . '@example.com',
            'street' => 'Test Client Street 123',
            'city' => 'Test Client City',
            'zip' => '54321',
            'country' => 'SK',
            'ico' => '87654321',
            'dic' => 'SK87654321',
            'phone' => '+421987654321',
            'description' => 'Test client description',
            'is_default' => false,
        ];
    }

    /**
     * Create necessary permissions and roles for frontend client testing
     * Sets up web guard permissions and frontend_user role
     */
    private function createPermissionsAndRoles(): void
    {
        // Frontend permissions
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.clients', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_supplier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_product', 'guard_name' => 'web']);
        
        // Create frontend role
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.clients',
            'frontend.can_delete_products',
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_supplier',
            'frontend.can_create_edit_product'
        ]);
    }

    /**
     * Create test user with proper frontend roles and permissions
     * Creates a user with frontend_user role for client management
     */
    private function createTestUser(): void
    {
        // Create test user
        $this->validEmail = 'test-' . uniqid() . '@example.com';
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => $this->validEmail,
        ]);
        
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $this->user->assignRole($frontendRole);
    }

    /**
     * Helper method to create a user with frontend_user role
     */
    private function createUserWithRole(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $frontendRole = Role::where('name', 'frontend_user')->where('guard_name', 'web')->first();
        $user->assignRole($frontendRole);
        return $user;
    }

    /**
     * Test index returns correct view
     */
    #[Test]
    public function index_returns_correct_view()
    {
        $response = $this->actingAs($this->user)->get($this->localizedRoute('frontend.clients'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.clients.index');
    }

    /**
     * Test index requires authentication
     */
    #[Test]
    public function index_requires_authentication()
    {
        $response = $this->get($this->localizedRoute('frontend.clients'));

        $response->assertRedirect('/login');
    }

    /**
     * Test create returns correct view with data
     */
    #[Test]
    public function create_returns_correct_view_with_data()
    {
        $response = $this->actingAs($this->user)->get($this->localizedRoute('frontend.client.create'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.clients.create');
        $response->assertViewHas(['fields', 'userInfo', 'countries']);
        
        // Check that userInfo contains user data
        $viewData = $response->viewData('userInfo');
        $this->assertEquals($this->user->name, $viewData['name']);
        $this->assertEquals($this->user->email, $viewData['email']);
    }

    /**
     * Test create requires authentication
     */
    #[Test]
    public function create_requires_authentication()
    {
        $response = $this->get($this->localizedRoute('frontend.client.create'));

        $response->assertRedirect('/login');
    }

    /**
     * Test store creates client successfully
     */
    #[Test]
    public function store_creates_client_successfully()
    {
        $response = $this->actingAs($this->user)
            ->post($this->localizedRoute('frontend.client.store'), $this->validClientData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('clients', [
            'name' => $this->validClientData['name'],
            'email' => $this->validClientData['email'],
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test store requires authentication
     */
    #[Test]
    public function store_requires_authentication()
    {
        $response = $this->post($this->localizedRoute('frontend.client.store'), $this->validClientData);

        $response->assertRedirect('/login');
        $this->assertDatabaseMissing('clients', ['email' => $this->validClientData['email']]);
    }

    /**
     * Test store fails with invalid data
     */
    #[Test]
    public function store_fails_with_invalid_data()
    {
        $invalidData = $this->validClientData;
        unset($invalidData['name']); // Remove required field

        $response = $this->actingAs($this->user)
            ->post($this->localizedRoute('frontend.client.store'), $invalidData);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test store handles exceptions gracefully
     */
    #[Test]
    public function store_handles_exceptions_gracefully()
    {
        // Mock the ClientRepository to throw an exception
        $this->mock(ClientRepository::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        $response = $this->actingAs($this->user)
            ->post($this->localizedRoute('frontend.client.store'), $this->validClientData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test show displays client details
     */
    #[Test]
    public function show_displays_client_details()
    {
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.show', $client->id));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.clients.show');
        $response->assertViewHas('client', $client);
        $response->assertViewHas('invoices');
    }

    /**
     * Test show requires authentication
     */
    #[Test]
    public function show_requires_authentication()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get($this->localizedRoute('frontend.client.show', $client->id));

        $response->assertRedirect('/login');
    }

    /**
     * Test show prevents access to other users' clients
     */
    #[Test]
    public function show_prevents_access_to_other_users_clients()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $client = Client::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.show', $client->id));

        $response->assertRedirect();
        $this->assertStringContainsString($this->localizedRoute('frontend.clients'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test show handles non-numeric IDs
     * With int typehint, Laravel will throw TypeError (500) for non-numeric values
     */
    #[Test]
    public function show_handles_non_numeric_ids()
    {
        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.show', 'invalid-id'));

        // With int typehint, Laravel throws TypeError which results in 500 status
        $response->assertStatus(500);
    }

    /**
     * Test show ignores static file requests
     * With int typehint, Laravel will throw TypeError (500) for non-numeric values like 'test.js'
     */
    #[Test]
    public function show_ignores_static_file_requests()
    {
        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.show', 'test.js'));

        // With int typehint, Laravel throws TypeError which results in 500 status
        $response->assertStatus(500);
    }

    /**
     * Test show displays client with invoices
     */
    #[Test]
    public function show_displays_client_with_invoices()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'payment_method_id' => null, // Set to null to avoid foreign key issues
        ]);

        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.show', $client->id));

        $response->assertStatus(200);
        $response->assertViewHas('invoices');
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals($invoice->id, $invoices->first()->id);
    }

    /**
     * Test edit returns correct view with client data
     */
    #[Test]
    public function edit_returns_correct_view_with_client_data()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.edit', $client->id));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.clients.edit');
        $response->assertViewHasAll(['client', 'fields', 'countries']);
        $response->assertViewHas('client', $client);
    }

    /**
     * Test edit requires authentication
     */
    #[Test]
    public function edit_requires_authentication()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get($this->localizedRoute('frontend.client.edit', $client->id));

        $response->assertRedirect('/login');
    }

    /**
     * Test edit prevents access to other users' clients
     */
    #[Test]
    public function edit_prevents_access_to_other_users_clients()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $client = Client::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get($this->localizedRoute('frontend.client.edit', $client->id));

        $response->assertRedirect();
        $this->assertStringContainsString($this->localizedRoute('frontend.clients'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test update updates client successfully
     */
    #[Test]
    public function update_updates_client_successfully()
    {
        $client = Client::factory()->create([
            'name' => 'Old Name',
            'user_id' => $this->user->id,
        ]);

        $updateData = $this->validClientData;
        $updateData['name'] = 'Updated Client Name';

        $response = $this->actingAs($this->user)
            ->put($this->localizedRoute('frontend.client.update', $client->id), $updateData);

        $response->assertRedirect();
        $this->assertStringContainsString($this->localizedRoute('frontend.clients'), $response->headers->get('Location'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client Name',
        ]);
    }

    /**
     * Test update requires authentication
     */
    #[Test]
    public function update_requires_authentication()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put($this->localizedRoute('frontend.client.update', $client->id), $this->validClientData);

        $response->assertRedirect('/login');
    }

    /**
     * Test update prevents updating other users' clients
     */
    #[Test]
    public function update_prevents_updating_other_users_clients()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $client = Client::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put($this->localizedRoute('frontend.client.update', $client->id), $this->validClientData);

        $response->assertRedirect();
        $this->assertStringContainsString($this->localizedRoute('frontend.clients'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test update handles validation errors
     */
    #[Test]
    public function update_handles_validation_errors()
    {
        $client = Client::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invalidData = $this->validClientData;
        $invalidData['email'] = 'invalid-email';

        $response = $this->actingAs($this->user)
            ->put($this->localizedRoute('frontend.client.update', $client->id), $invalidData);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test destroy deletes client without invoices
     */
    #[Test]
    public function destroy_deletes_client_without_invoices()
    {
        $this->actingAs($this->user);

        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $response = $this->delete($this->localizedRoute('frontend.client.destroy', $client->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
    }

    /**
     * Test destroy method requires authentication.
     *
     * @return void
     */
    #[Test]
    public function destroy_requires_authentication()
    {
        $client = Client::factory()->create();

        $response = $this->get($this->localizedRoute('frontend.client.destroy', $client->id));

        $response->assertRedirect('/login');
        
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    /**
     * Test destroy method prevents deleting other user's clients.
     *
     * @return void
     */
    #[Test]
    public function destroy_prevents_deleting_other_users_clients()
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get($this->localizedRoute('frontend.client.destroy', $otherClient->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('clients', ['id' => $otherClient->id]);
    }

    /**
     * Test destroy method prevents deleting client with invoices.
     *
     * @return void
     */
    #[Test]
    public function destroy_prevents_deleting_client_with_invoices()
    {
        $this->actingAs($this->user);

        $client = Client::factory()->create(['user_id' => $this->user->id]);
        
        // Create invoice without payment_method_id to avoid foreign key issues
        Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $this->user->id,
            'payment_method_id' => null
        ]);

        $response = $this->delete($this->localizedRoute('frontend.client.destroy', $client->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    /**
     * Test setDefault method sets client as default.
     *
     * @return void
     */
    #[Test]
    public function set_default_sets_client_as_default()
    {
        $this->actingAs($this->user);

        // Create multiple clients
        $client1 = Client::factory()->create(['user_id' => $this->user->id, 'is_default' => true]);
        $client2 = Client::factory()->create(['user_id' => $this->user->id, 'is_default' => false]);

        $response = $this->get($this->localizedRoute('frontend.client.set-default', $client2->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Check that client2 is now default and client1 is not
        $client1->refresh();
        $client2->refresh();
        
        $this->assertFalse($client1->is_default);
        $this->assertTrue($client2->is_default);
    }

    /**
     * Test setDefault method requires authentication.
     *
     * @return void
     */
    #[Test]
    public function set_default_requires_authentication()
    {
        $client = Client::factory()->create();

        $response = $this->get($this->localizedRoute('frontend.client.set-default', $client->id));

        $response->assertRedirect('/login');
    }

    /**
     * Test setDefault method prevents setting other user's client as default.
     *
     * @return void
     */
    #[Test]
    public function set_default_prevents_setting_other_users_client_as_default()
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get($this->localizedRoute('frontend.client.set-default', $otherClient->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test controller uses ClientFormFields trait.
     *
     * @return void
     */
    #[Test]
    public function controller_uses_client_form_fields_trait()
    {
        $traits = class_uses(ClientController::class);

        $this->assertContains(\App\Traits\ClientFormFields::class, $traits);
    }

    /**
     * Test controller handles locale properly in redirects.
     *
     * @return void
     */
    #[Test]
    public function controller_handles_locale_in_redirects()
    {
        $this->actingAs($this->user);

        $dataWithLang = $this->validClientData;

        $response = $this->post($this->localizedRoute('frontend.client.store'), $dataWithLang);

        $response->assertRedirect();
        // Check that the redirect contains the default locale (cs)
        $targetUrl = $response->getTargetUrl();
        $this->assertStringContainsString('/cs/', $targetUrl);
    }

    /**
     * Test controller dependency injection.
     *
     * @return void
     */
    #[Test]
    public function controller_dependency_injection()
    {
        $clientRepository = $this->createMock(ClientRepository::class);
        $countryService = $this->createMock(CountryService::class);
        $localeService = $this->createMock(LocaleService::class);

        $controller = new ClientController(
            $clientRepository,
            $countryService,
            $localeService
        );

        $this->assertInstanceOf(ClientController::class, $controller);
    }

    /**
     * Test error logging on exceptions.
     *
     * @return void
     */
    #[Test]
    public function error_logging_on_exceptions()
    {
        $this->actingAs($this->user);
        
        // Mock the ClientRepository to throw an exception
        $this->mock(ClientRepository::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andThrow(new \Exception('Database error'));
        });
        
        Log::shouldReceive('error')->once()->with(\Mockery::pattern('/Error creating client:/'));

        $response = $this->post($this->localizedRoute('frontend.client.store'), $this->validClientData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
