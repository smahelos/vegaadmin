<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature test for Admin InvoiceRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class InvoiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $invoiceUser;
    private Client $client;
    private Supplier $supplier;
    private PaymentMethod $paymentMethod;

    /**
     * Set up test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->invoiceUser = User::factory()->create();
        $this->client = Client::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();
        
        // Create necessary permissions for testing
        $this->createRequiredPermissions();
        
        // Define test routes
        Route::post('/admin/invoice', function (InvoiceRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');
        
        Route::put('/admin/invoice/{id}', function (InvoiceRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
    }

    /**
     * Create required permissions for testing.
     */
    private function createRequiredPermissions(): void
    {
        // Define all permissions required for admin operations and navigation
        $permissions = [
            // User management permissions
            'can_create_edit_user',
            
            // Business operations permissions
            'can_create_edit_invoice',
            'can_create_edit_client',
            'can_create_edit_supplier',
            
            // Financial management permissions
            'can_create_edit_expense',
            'can_create_edit_tax',
            'can_create_edit_bank',
            'can_create_edit_payment_method',
            
            // Inventory management permissions
            'can_create_edit_product',
            
            // System administration permissions
            'can_create_edit_command',
            'can_create_edit_cron_task',
            'can_create_edit_status',
            'can_configure_system',
            
            // Basic backpack access
            'backpack.access',
        ];

        // Create all permissions for backpack guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission, 
                'guard_name' => 'backpack'
            ]);
        }

        // Give the user all necessary permissions for the backpack guard
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)
                ->where('guard_name', 'backpack')
                ->first();
            if ($permission) {
                $this->user->givePermissionTo($permission);
            }
        }
    }

    #[Test]
    public function validation_passes_with_complete_valid_data(): void
    {
        $validData = [
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'invoice_ks' => '0308',
            'invoice_ss' => 'SS123',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1500.50,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'tax_point_date' => '2024-01-15',
            'ico' => '12345678',
            'dic' => 'CZ12345678',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'invoice_text' => 'Service invoice for consulting',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data(): void
    {
        $minimalData = [
            'supplier_id' => $this->supplier->id, // Required in admin version
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_name_when_supplier_id_missing(): void
    {
        $validData = [
            'name' => 'External Supplier Name',
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_missing_required_fields(): void
    {
        $request = new InvoiceRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('client_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('invoice_vs', $validator->errors()->toArray());
        $this->assertArrayHasKey('due_in', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_method_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_status', $validator->errors()->toArray());
        $this->assertArrayHasKey('payment_currency', $validator->errors()->toArray());
        $this->assertArrayHasKey('issue_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('street', $validator->errors()->toArray());
        $this->assertArrayHasKey('city', $validator->errors()->toArray());
        $this->assertArrayHasKey('zip', $validator->errors()->toArray());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_client_id(): void
    {
        $invalidData = [
            'client_id' => 99999, // Non-existent client
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('client_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_supplier_id(): void
    {
        $invalidData = [
            'supplier_id' => 99999, // Non-existent supplier
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_payment_method_id(): void
    {
        $invalidData = [
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => 99999, // Non-existent payment method
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('payment_method_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_user_id(): void
    {
        $invalidData = [
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => 99999, // Non-existent user
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_negative_due_in(): void
    {
        $invalidData = [
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 0, // Below minimum of 1
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('due_in', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_negative_payment_amount(): void
    {
        $invalidData = [
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => -100.00, // Negative amount
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('payment_amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_date_format(): void
    {
        $invalidData = [
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => 'invalid-date',
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('issue_date', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_nullable_fields(): void
    {
        $validData = [
            'supplier_id' => $this->supplier->id, // Required field in admin version
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2024001',
            'invoice_ks' => null,
            'invoice_ss' => null,
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 1000.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'tax_point_date' => null,
            'ico' => null,
            'dic' => null,
            'street' => '123 Business Street',
            'city' => 'Prague',
            'zip' => '11000',
            'country' => 'Czech Republic',
            'invoice_text' => null,
            'user_id' => $this->invoiceUser->id,
        ];

        $request = new InvoiceRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function authorization_passes_for_authenticated_user(): void
    {
        $this->actingAs($this->user, 'backpack')
             ->withoutMiddleware()
             ->postJson('/admin/invoice', [
                 'supplier_id' => $this->supplier->id, // Include required field
                 'client_id' => $this->client->id,
                 'invoice_vs' => 'INV2024001',
                 'due_in' => 30,
                 'payment_method_id' => $this->paymentMethod->id,
                 'payment_amount' => 1000.00,
                 'payment_status' => 'pending',
                 'payment_currency' => 'CZK',
                 'issue_date' => '2024-01-15',
                 'street' => '123 Business Street',
                 'city' => 'Prague',
                 'zip' => '11000',
                 'country' => 'Czech Republic',
                 'user_id' => $this->invoiceUser->id,
             ])
             ->assertStatus(200);
    }

    #[Test]
    public function authorization_fails_for_unauthenticated_user(): void
    {
        $this->withoutMiddleware()
             ->postJson('/admin/invoice', [
                 'client_id' => $this->client->id,
                 'invoice_vs' => 'INV2024001',
                 'due_in' => 30,
                 'payment_method_id' => $this->paymentMethod->id,
                 'payment_amount' => 1000.00,
                 'payment_status' => 'pending',
                 'payment_currency' => 'CZK',
                 'issue_date' => '2024-01-15',
                 'street' => '123 Business Street',
                 'city' => 'Prague',
                 'zip' => '11000',
                 'country' => 'Czech Republic',
                 'user_id' => $this->invoiceUser->id,
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function attributes_method_returns_correct_translations(): void
    {
        $request = new InvoiceRequest();
        $attributes = $request->attributes();

        $this->assertArrayHasKey('client_id', $attributes);
        $this->assertArrayHasKey('supplier_id', $attributes);
        $this->assertArrayHasKey('invoice_vs', $attributes);
        $this->assertArrayHasKey('payment_amount', $attributes);
        $this->assertArrayHasKey('issue_date', $attributes);
        
        // Check that translations are being called
        $this->assertEquals(__('invoices.fields.client'), $attributes['client_id']);
        $this->assertEquals(__('invoices.fields.supplier'), $attributes['supplier_id']);
        $this->assertEquals(__('invoices.fields.invoice_number'), $attributes['invoice_vs']);
        $this->assertEquals(__('invoices.fields.amount'), $attributes['payment_amount']);
        $this->assertEquals(__('invoices.fields.issue_date'), $attributes['issue_date']);
    }

    #[Test]
    public function messages_method_returns_correct_translations(): void
    {
        $request = new InvoiceRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('supplier_id.required_without', $messages);
        $this->assertArrayHasKey('name.required_without', $messages);
        $this->assertArrayHasKey('client_id.required', $messages);
        $this->assertArrayHasKey('user_id.required', $messages);
        
        // Check that translations are being called
        $this->assertEquals(__('invoices.validation.supplier_required'), $messages['supplier_id.required_without']);
        $this->assertEquals(__('invoices.validation.supplier_required'), $messages['name.required_without']);
        $this->assertEquals(__('invoices.validation.client_required'), $messages['client_id.required']);
        $this->assertEquals(__('invoices.validation.user_required'), $messages['user_id.required']);
    }
}
