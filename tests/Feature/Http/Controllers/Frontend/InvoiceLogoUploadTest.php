<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\EntityLimit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for invoice logo upload functionality
 * 
 * Tests complete HTTP flow of invoice logo upload, validation, and storage
 */
class InvoiceLogoUploadTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesFrontendTestEnvironment;

    protected User $user;
    protected PaymentMethod $paymentMethod;
    protected Status $paymentStatus;
    protected Supplier $supplier;
    protected Client $client;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();

        // Create required permissions for 'web' guard
        $permission = Permission::where('name', 'frontend.can_create_edit_invoice')
                               ->where('guard_name', 'web')
                               ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }

        // Create related models
        $this->paymentMethod = PaymentMethod::factory()->create();
        $this->paymentStatus = Status::factory()->create();
        $this->supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        $this->client = Client::factory()->create(['user_id' => $this->user->id]);

        // Set up fake disk for file uploads
        Storage::fake('public');

        // Create entity limit for invoices
        $this->createInvoiceEntityLimit();
    }

    /**
     * Create entity limit for invoices to allow test operations
     */
    private function createInvoiceEntityLimit(): void
    {
        EntityLimit::create([
            'permission_name' => 'frontend.can_create_edit_invoice',
            'entity_type' => 'invoice',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Invoice limit for frontend tests',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function invoice_can_be_created_with_logo_upload()
    {
        $this->actingAs($this->user, 'web');

        // Create a fake image file
        $logoFile = UploadedFile::fake()->image('invoice_logo.png', 100, 100)->size(1000);

        $invoiceData = [
            'invoice_vs' => 'TEST001',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
            'payment_amount' => 1000.00,
            'payment_currency' => 'CZK',
            'issue_date' => now()->toDateString(),
            'due_in' => 30,
            'invoice_text' => 'Test invoice description',
            'invoice_logo' => $logoFile,
        ];

        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        $response->assertRedirect();
        // Check that redirect indicates success (contains 'invoice' route)
        $this->assertTrue(str_contains($response->headers->get('Location'), 'invoice'));
        
        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => 'TEST001',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
        ]);

        // Verify invoice logo was stored
        $invoice = Invoice::where('invoice_vs', 'TEST001')->first();
        $this->assertNotNull($invoice->invoice_logo);
        $this->assertStringContainsString('invoices/logos', $invoice->invoice_logo);
        
        // Verify file was actually uploaded
        $this->assertTrue(Storage::disk('public')->exists($invoice->invoice_logo));
    }

    #[Test]
    public function invoice_creation_fails_with_invalid_logo_format()
    {
        $this->actingAs($this->user);

        // Create a fake non-image file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $invoiceData = [
            'invoice_vs' => 'TEST002',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
            'payment_amount' => 1000.00,
            'payment_currency' => 'CZK',
            'issue_date' => now()->toDateString(),
            'due_in' => 30,
            'invoice_text' => 'Test invoice description',
            'invoice_logo' => $invalidFile,
        ];

        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        $response->assertSessionHasErrors('invoice_logo');
        
        // Verify invoice was not created
        $this->assertDatabaseMissing('invoices', [
            'invoice_vs' => 'TEST002',
        ]);
    }

    #[Test]
    public function invoice_creation_fails_with_oversized_logo()
    {
        $this->actingAs($this->user);

        // Create a fake image file that's too large (3MB)
        $oversizedFile = UploadedFile::fake()->image('large_logo.png', 100, 100)->size(3000);

        $invoiceData = [
            'invoice_vs' => 'TEST003',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
            'payment_amount' => 1000.00,
            'payment_currency' => 'CZK',
            'issue_date' => now()->toDateString(),
            'due_in' => 30,
            'invoice_text' => 'Test invoice description',
            'invoice_logo' => $oversizedFile,
        ];

        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        $response->assertSessionHasErrors('invoice_logo');
        
        // Verify invoice was not created
        $this->assertDatabaseMissing('invoices', [
            'invoice_vs' => 'TEST003',
        ]);
    }

    #[Test]
    public function invoice_can_be_updated_with_logo_upload()
    {
        $this->actingAs($this->user);

        // Create an existing invoice
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
        ]);

        // Create a fake image file for update
        $newLogoFile = UploadedFile::fake()->image('updated_logo.png', 100, 100)->size(800);

        $updateData = [
            'invoice_vs' => $invoice->invoice_vs,
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
            'payment_amount' => 1500.00,
            'payment_currency' => 'CZK',
            'issue_date' => now()->toDateString(),
            'due_in' => 30,
            'invoice_text' => 'Updated invoice description',
            'invoice_logo' => $newLogoFile,
        ];

        $response = $this->put(route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        // Verify invoice was updated
        $invoice->refresh();
        $this->assertNotNull($invoice->invoice_logo);
        $this->assertStringContainsString('invoices/logos', $invoice->invoice_logo);
        
        // Verify file was actually uploaded
        $this->assertTrue(Storage::disk('public')->exists($invoice->invoice_logo));
    }

    #[Test]
    public function invoice_can_be_created_without_logo()
    {
        $this->actingAs($this->user);

        $invoiceData = [
            'invoice_vs' => 'TEST004',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_status_id' => $this->paymentStatus->id,
            'payment_amount' => 1000.00,
            'payment_currency' => 'CZK',
            'issue_date' => now()->toDateString(),
            'due_in' => 30,
            'invoice_text' => 'Test invoice without logo',
            // No invoice_logo field
        ];

        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        $response->assertRedirect();
        
        // Check that the redirect location indicates success (contains 'invoice' route) 
        // If there were validation errors, we would get redirected back to create form
        $this->assertTrue(str_contains($response->headers->get('Location'), 'invoice'));
        
        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => 'TEST004',
            'supplier_id' => $this->supplier->id,
            'client_id' => $this->client->id,
        ]);

        // Verify invoice logo is null or empty
        $invoice = Invoice::where('invoice_vs', 'TEST004')->first();
        $this->assertTrue(empty($invoice->invoice_logo));
    }
}
