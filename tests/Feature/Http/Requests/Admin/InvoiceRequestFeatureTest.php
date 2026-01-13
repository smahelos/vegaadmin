<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\EntityLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Feature test for Admin InvoiceRequest class.
 * Tests validation rules, authorization logic, and custom attributes/messages.
 */
class InvoiceRequestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesAdminTestEnvironment;

    private UniversalLimitService $limitService;
    protected User $adminUser;
    protected User $regularUser;
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

        // Set up admin test environment with roles and permissions
        $this->setUpAdminTestEnvironment();
        $permission = Permission::where('name', 'can_create_edit_invoice')
            ->where('guard_name', 'backpack')
            ->first();
        $this->regularUser->givePermissionTo($permission);

        $this->invoiceUser = User::factory()->create();
        $this->client = Client::factory()->create();
        $this->supplier = Supplier::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();

        $this->limitService = app(UniversalLimitService::class);

        // Define test routes
        Route::post('/test-invoice', function (InvoiceRequest $request) {
            return response()->json(['success' => true]);
        })->middleware('web');

        Route::put('/test-invoice/{id}', function (InvoiceRequest $request, $id) {
            return response()->json(['success' => true]);
        })->middleware('web');
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
        $this->actingAs($this->adminUser, 'backpack');

        // Set a generous limit for invoices (high enough to not interfere)
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 1000, // High limit to avoid interference
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $this->postJson('/test-invoice', [
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
        // Set a generous limit for invoices (won't affect unauthenticated test)
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 1000,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

        $this->postJson('/test-invoice', [
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

    #[Test]
    public function invoice_creation_respects_global_entity_limits(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        // Strict limit 1
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);

    // First creation attempt (should pass and NOT automatically record usage)
    $this->postJson('/test-invoice', [
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV1'.uniqid(),
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 100.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11000',
            'country' => 'CZ',
            'user_id' => $this->invoiceUser->id,
        ])->assertStatus(200);

    // Record usage manually because BaseEntityRequest::authorize() only checks limits
    // The original separate authorization test explicitly recorded usage after first authorize()
    $this->limitService->recordUsage($this->adminUser->id, 'invoice', 'count', 'monthly', 'backpack');

        // Second should exceed
        $this->expectException(\App\Domain\User\Exceptions\EntityLimitExceededException::class);
        $request = new class extends InvoiceRequest { public function rules(): array { return []; } };
        $request->replace([
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV2'.uniqid(),
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 120.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-16',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11000',
            'country' => 'CZ',
            'user_id' => $this->invoiceUser->id,
        ]);
        $request->setRouteResolver(fn() => (object)['parameter'=>fn($n)=> null]);
        $request->setMethod('POST');
        $request->authorize();
    }

    #[Test]
    public function authorization_fails_for_user_without_permission(): void
    {
        // User without the can_create_edit_invoice permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission, 'backpack');

        $this->postJson('/test-invoice', [
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV-NOPERM',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 100.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11000',
            'country' => 'CZ',
            'user_id' => $this->invoiceUser->id,
        ])->assertStatus(403);
    }

    #[Test]
    public function invoice_update_bypasses_limit_checks(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        EntityLimit::factory()->create([
            'permission_name' => 'can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 0,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);
        $request = new class extends InvoiceRequest { public function rules(): array { return []; } };
        $request->replace([
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV-UPDATE',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 200.00,
            'payment_status' => 'paid',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11000',
            'country' => 'CZ',
            'user_id' => $this->invoiceUser->id,
        ]);
        $request->setRouteResolver(fn()=> (object)['parameter'=>fn($n)=> 'invoice-id']);
        $request->setMethod('PUT');
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function user_with_unlimited_access_bypasses_limits(): void
    {
        // Ensure both required permissions exist on backpack guard
        $editPermission = Permission::firstOrCreate(['name'=>'can_create_edit_invoice','guard_name'=>'backpack']);
        $unlimitedPermission = Permission::firstOrCreate(['name'=>'can_create_invoice_unlimited','guard_name'=>'backpack']);

        $unlimitedUser = User::factory()->create();
        $unlimitedUser->givePermissionTo($editPermission);
        $unlimitedUser->givePermissionTo($unlimitedPermission);
        $this->actingAs($unlimitedUser, 'backpack');

        EntityLimit::factory()->create([
            'permission_name' => 'can_create_invoice_unlimited',
            'entity_type' => 'invoice',
            'limit_value' => 0,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'is_active' => true,
        ]);
        $request = new class extends InvoiceRequest { public function rules(): array { return []; } };
        $request->replace([
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'invoice_vs' => 'INV-UNL',
            'due_in' => 30,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_amount' => 300.00,
            'payment_status' => 'pending',
            'payment_currency' => 'CZK',
            'issue_date' => '2024-01-15',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11000',
            'country' => 'CZ',
            'user_id' => $unlimitedUser->id,
        ]);
        $request->setRouteResolver(fn()=> (object)['parameter'=>fn($n)=> null]);
        $request->setMethod('POST');
        $this->assertTrue($request->authorize());
    }
}
