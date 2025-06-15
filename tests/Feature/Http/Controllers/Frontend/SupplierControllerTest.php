<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Http\Controllers\Frontend\SupplierController;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Invoice;
use App\Services\CountryService;
use App\Services\LocaleService;
use App\Services\BankService;
use App\Repositories\SupplierRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature tests for Frontend\SupplierController
 * 
 * Tests all frontend supplier management endpoints: index, create, store, show, edit, update, destroy, setDefault
 * Tests authentication scenarios, authorization (user ownership checks), validation, error handling
 * Tests view rendering, form processing, and security boundaries for supplier management
 */
class SupplierControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected string $validEmail;
    protected array $validSupplierData;

    /**
     * Set up the test environment.
     * Creates permissions, roles, test user, and valid test data for supplier operations.
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

        // Set up valid supplier data for testing
        $this->setupValidSupplierData();
    }

    /**
     * Setup valid supplier data for testing
     */
    private function setupValidSupplierData(): void
    {
        $this->validSupplierData = [
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'phone' => '+420123456789',
            'street' => 'Test Street 123',
            'city' => 'Prague',
            'zip' => '12000',
            'country' => 'CZ',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'description' => 'Test supplier description',
            'is_default' => false,
            'account_number' => '123456789',
            'bank_code' => '0100',
            'iban' => 'CZ6508000000192000145399',
            'swift' => 'GIBACZPX',
            'bank_name' => 'Test Bank',
        ];
    }

    /**
     * Create necessary permissions and roles for frontend supplier testing
     * Sets up web guard permissions and frontend_user role
     */
    private function createPermissionsAndRoles(): void
    {
        // Frontend permissions
        Permission::firstOrCreate(['name' => 'frontend.api.access', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.api.suppliers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_delete_products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_client', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_supplier', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_product', 'guard_name' => 'web']);
        
        // Create frontend role
        $frontendRole = Role::firstOrCreate(['name' => 'frontend_user', 'guard_name' => 'web']);
        $frontendRole->syncPermissions([
            'frontend.api.access',
            'frontend.api.suppliers',
            'frontend.can_delete_products',
            'frontend.can_create_edit_client',
            'frontend.can_create_edit_supplier',
            'frontend.can_create_edit_product'
        ]);
    }

    /**
     * Create test user with proper frontend roles and permissions
     * Creates a user with frontend_user role for supplier management
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
     * Test index method returns correct view
     */
    public function test_index_returns_correct_view()
    {
        $response = $this->actingAs($this->user)->get(route('frontend.suppliers'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.suppliers.index');
    }

    /**
     * Test index requires authentication
     */
    public function test_index_requires_authentication()
    {
        $response = $this->get(route('frontend.suppliers'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test create method returns correct view with data
     */
    public function test_create_returns_correct_view_with_data()
    {
        // Clean any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start output buffering to catch any stray output
        ob_start();
        
        try {
            $response = $this->actingAs($this->user)->get(route('frontend.supplier.create'));

            $response->assertStatus(200);
            $response->assertViewIs('frontend.suppliers.create');
            $response->assertViewHas('fields');
            $response->assertViewHas('supplierInfo');
            $response->assertViewHas('banks');
            $response->assertViewHas('banksData');
            $response->assertViewHas('countries');
        } finally {
            // Clean any captured output
            if (ob_get_level()) {
                ob_end_clean();
            }
        }
    }

    /**
     * Test create requires authentication
     */
    public function test_create_requires_authentication()
    {
        $response = $this->get(route('frontend.supplier.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test store creates supplier successfully
     */
    public function test_store_creates_supplier_successfully()
    {
        $response = $this->actingAs($this->user)
            ->post(route('frontend.supplier.store'), $this->validSupplierData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test store requires authentication
     */
    public function test_store_requires_authentication()
    {
        $response = $this->post(route('frontend.supplier.store'), $this->validSupplierData);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test store fails with invalid data
     */
    public function test_store_fails_with_invalid_data()
    {
        $invalidData = $this->validSupplierData;
        unset($invalidData['name']);

        $response = $this->actingAs($this->user)
            ->post(route('frontend.supplier.store'), $invalidData);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Test store handles exceptions gracefully
     */
    public function test_store_handles_exceptions_gracefully()
    {
        // Mock the SupplierRepository to throw an exception
        $this->mock(SupplierRepository::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        $response = $this->actingAs($this->user)
            ->post(route('frontend.supplier.store'), $this->validSupplierData);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test show displays supplier details
     */
    public function test_show_displays_supplier_details()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Supplier',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.show', $supplier->id));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.suppliers.show');
        $response->assertViewHas('supplier', $supplier);
        $response->assertViewHas('invoices');
    }

    /**
     * Test show requires authentication
     */
    public function test_show_requires_authentication()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('frontend.supplier.show', $supplier->id));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test show prevents access to other users' suppliers
     */
    public function test_show_prevents_access_to_other_users_suppliers()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $supplier = Supplier::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.show', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test show handles non-numeric IDs
     */
    public function test_show_handles_non_numeric_ids()
    {
        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.show', 'invalid-id'));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test show ignores static file requests
     */
    public function test_show_ignores_static_file_requests()
    {
        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.show', 'test.js'));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Not found']);
    }

    /**
     * Test show displays supplier with invoices
     */
    public function test_show_displays_supplier_with_invoices()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => null, // Set to null to avoid foreign key issues
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.show', $supplier->id));

        $response->assertStatus(200);
        $response->assertViewHas('invoices');
        $invoices = $response->viewData('invoices');
        $this->assertCount(1, $invoices);
        $this->assertEquals($invoice->id, $invoices->first()->id);
    }

    /**
     * Test edit returns correct view with supplier data
     */
    public function test_edit_returns_correct_view_with_supplier_data()
    {
        // Clean any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start output buffering to catch any stray output
        ob_start();
        
        try {
            $supplier = Supplier::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Supplier',
            ]);

            $response = $this->actingAs($this->user)
                ->get(route('frontend.supplier.edit', $supplier->id));

            $response->assertStatus(200);
            $response->assertViewIs('frontend.suppliers.edit');
            $response->assertViewHasAll(['supplier', 'fields', 'banks', 'banksData', 'countries']);
        } finally {
            // Clean any captured output
            if (ob_get_level()) {
                ob_end_clean();
            }
        }
    }

    /**
     * Test edit requires authentication
     */
    public function test_edit_requires_authentication()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('frontend.supplier.edit', $supplier->id));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test edit prevents access to other users' suppliers
     */
    public function test_edit_prevents_access_to_other_users_suppliers()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $supplier = Supplier::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.edit', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test update updates supplier successfully
     */
    public function test_update_updates_supplier_successfully()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $updateData = $this->validSupplierData;
        $updateData['name'] = 'Updated Supplier Name';

        $response = $this->actingAs($this->user)
            ->put(route('frontend.supplier.update', $supplier->id), $updateData);

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier Name',
        ]);
    }

    /**
     * Test update requires authentication
     */
    public function test_update_requires_authentication()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->put(route('frontend.supplier.update', $supplier->id), $this->validSupplierData);

        $response->assertRedirect(route('login'));
    }

    /**
     * Test update prevents updating other users' suppliers
     */
    public function test_update_prevents_updating_other_users_suppliers()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $supplier = Supplier::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('frontend.supplier.update', $supplier->id), $this->validSupplierData);

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test update handles validation errors
     */
    public function test_update_handles_validation_errors()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $invalidData = $this->validSupplierData;
        $invalidData['email'] = 'invalid-email';

        $response = $this->actingAs($this->user)
            ->put(route('frontend.supplier.update', $supplier->id), $invalidData);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test destroy deletes supplier without invoices
     */
    public function test_destroy_deletes_supplier_without_invoices()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('frontend.supplier.destroy', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    /**
     * Test destroy requires authentication
     */
    public function test_destroy_requires_authentication()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('frontend.supplier.destroy', $supplier->id));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test destroy prevents deleting other users' suppliers
     */
    public function test_destroy_prevents_deleting_other_users_suppliers()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $supplier = Supplier::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('frontend.supplier.destroy', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test destroy prevents deleting supplier with invoices
     */
    public function test_destroy_prevents_deleting_supplier_with_invoices()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create invoice with valid payment method ID
        Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => null, // Set to null to avoid foreign key issues
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('frontend.supplier.destroy', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    /**
     * Test set default sets supplier as default
     */
    public function test_set_default_sets_supplier_as_default()
    {
        $supplier1 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        $supplier2 = Supplier::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.set-default', $supplier2->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier1->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier2->id,
            'is_default' => true,
        ]);
    }

    /**
     * Test set default requires authentication
     */
    public function test_set_default_requires_authentication()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('frontend.supplier.set-default', $supplier->id));

        $response->assertRedirect(route('login'));
    }

    /**
     * Test set default prevents setting other users' supplier as default
     */
    public function test_set_default_prevents_setting_other_users_supplier_as_default()
    {
        // Create another user with proper role
        $otherUser = $this->createUserWithRole();
        
        $supplier = Supplier::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('frontend.supplier.set-default', $supplier->id));

        $response->assertRedirect();
        $this->assertStringContainsString(route('frontend.suppliers'), $response->headers->get('Location'));
        $response->assertSessionHas('error');
    }

    /**
     * Test controller uses supplier form fields trait
     */
    public function test_controller_uses_supplier_form_fields_trait()
    {
        $controller = new SupplierController(
            app(BankService::class),
            app(LocaleService::class),
            app(CountryService::class),
            app(SupplierRepository::class)
        );

        $this->assertTrue(method_exists($controller, 'getSupplierFields'));
    }

    /**
     * Test controller handles locale in redirects
     */
    public function test_controller_handles_locale_in_redirects()
    {
        $supplier = Supplier::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('frontend.supplier.store'), array_merge($this->validSupplierData, ['lang' => 'cs']));

        $response->assertRedirect();
        $this->assertStringContainsString('lang=cs', $response->headers->get('Location'));
    }

    /**
     * Test controller dependency injection
     */
    public function test_controller_dependency_injection()
    {
        $controller = app(SupplierController::class);

        $reflection = new \ReflectionClass($controller);
        
        $bankServiceProperty = $reflection->getProperty('bankService');
        $bankServiceProperty->setAccessible(true);
        $this->assertInstanceOf(BankService::class, $bankServiceProperty->getValue($controller));

        $localeServiceProperty = $reflection->getProperty('localeService');
        $localeServiceProperty->setAccessible(true);
        $this->assertInstanceOf(LocaleService::class, $localeServiceProperty->getValue($controller));

        $countryServiceProperty = $reflection->getProperty('countryService');
        $countryServiceProperty->setAccessible(true);
        $this->assertInstanceOf(CountryService::class, $countryServiceProperty->getValue($controller));

        $supplierRepositoryProperty = $reflection->getProperty('supplierRepository');
        $supplierRepositoryProperty->setAccessible(true);
        $this->assertInstanceOf(SupplierRepository::class, $supplierRepositoryProperty->getValue($controller));
    }
}
