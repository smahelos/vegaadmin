<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\EntityLimit;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;

class InvoiceControllerFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

    private User $user;
    private Invoice $invoice;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();
        
        // Give user specific invoice permission
        $permission = Permission::where('name', 'frontend.can_create_edit_invoice')
                               ->where('guard_name', 'web')
                               ->first();
        if ($permission) {
            $this->user->givePermissionTo($permission);
        }

        // Create test invoice
        $this->invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'template' => 'default'
        ]);

        $this->createInvoiceEntityLimit();

        $this->limitService = app(UniversalLimitService::class);
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
    public function set_template_updates_invoice_template_successfully(): void
    {
        $this->actingAs($this->user, 'web');
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]), [
            'template' => 'modern'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify database was updated
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]);
    }

    #[Test]
    public function set_template_uses_request_template_over_route_parameter(): void
    {
        $this->actingAs($this->user, 'web');
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'minimal'
        ]), [
            'template' => 'modern'  // Request parameter should take precedence
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify request parameter was used
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]);
    }

    #[Test]
    public function set_template_defaults_to_default_template(): void
    {
        $this->actingAs($this->user, 'web');
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'default'
        ]));
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Verify default template was set
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'default'
        ]);
    }

    #[Test]
    public function set_template_fails_for_unauthenticated_user(): void
    {
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]), [
            'template' => 'modern'
        ]);
        
        $response->assertStatus(401);
    }

    #[Test]
    public function set_template_fails_for_non_existent_invoice(): void
    {
        $this->actingAs($this->user, 'web');
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => 99999,
            'template' => 'modern'
        ]), [
            'template' => 'modern'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function set_template_fails_for_other_users_invoice(): void
    {
        // Create another user and their invoice
        $otherUser = User::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'user_id' => $otherUser->id,
            'template' => 'default'
        ]);
        
        $this->actingAs($this->user, 'web');
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $otherInvoice->id,
            'template' => 'modern'
        ]), [
            'template' => 'modern'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        // Verify template was not changed
        $this->assertDatabaseHas('invoices', [
            'id' => $otherInvoice->id,
            'template' => 'default'
        ]);
    }

    #[Test]
    public function set_template_handles_service_exception(): void
    {
        $this->actingAs($this->user, 'web');
        
        // Mock the service to throw an exception
        $mockService = $this->mock(\App\Domain\Invoice\Contracts\InvoiceServiceInterface::class);
        $mockService->shouldReceive('setInvoiceTemplate')
                   ->with($this->invoice->id, 'modern')
                   ->andThrow(new \Exception('Service error'));
        
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]), [
            'template' => 'modern'
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function set_template_accepts_valid_template_names(): void
    {
        $this->actingAs($this->user, 'web');
        
        $validTemplates = ['default', 'modern', 'minimal'];
        
        foreach ($validTemplates as $template) {
            $response = $this->putJson(route('frontend.invoice.set-template', [
                'locale' => 'en',
                'id' => $this->invoice->id,
                'template' => $template
            ]), [
                'template' => $template
            ]);
            
            $response->assertRedirect();
            $response->assertSessionHas('success');
            
            $this->assertDatabaseHas('invoices', [
                'id' => $this->invoice->id,
                'template' => $template
            ]);
        }
    }

    #[Test]
    public function set_template_route_exists_and_has_correct_parameters(): void
    {
        $route = route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => 1,
            'template' => 'modern'
        ]);
        
        $this->assertStringContainsString('/invoice/1/set-template/modern', $route);
    }

    #[Test]
    public function invoice_creation_records_entity_usage(): void
    {
        $this->actingAs($this->user, 'web');
        
        // Create required related entities
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        $initialUsage = $initialCheck['current_usage'] ?? 0;

        $invoiceData = [
            'invoice_vs' => 'INV-' . uniqid(),
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->format('Y-m-d'),
            'due_in' => 30,
            'payment_amount' => 100.00,
            'payment_currency' => 'CZK',
            'street' => 'Test Street 123',
            'city' => 'Test City',
            'zip' => '12345',
            'country' => 'CZ',
            'invoice_text' => 'Test invoice text',
            'user_id' => $this->user->id,
        ];

        // Act
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        // Assert
        $response->assertRedirect(route('frontend.invoices', ['locale' => 'cs']));
        $response->assertSessionHas('success');

        // Check usage after creation
        $newCheck = $this->limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        $newUsage = $newCheck['current_usage'] ?? 0;
        
        $this->assertEquals($initialUsage + 1, $newUsage);

        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'invoice_vs' => $invoiceData['invoice_vs'],
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function invoice_creation_respects_entity_limits(): void
    {
        $this->actingAs($this->user, 'web');

        // Ensure the limit is set to 1 for this test
        // we need to set limit to 1, so update data in database
        EntityLimit::where([
            'permission_name' => 'frontend.can_create_edit_invoice'
        ])->update(['limit_value' => 1]);

        // Create required related entities
        $client = Client::factory()->create(['user_id' => $this->user->id]);
        $supplier = Supplier::factory()->create(['user_id' => $this->user->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();

        // First invoice creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        $this->assertTrue($checkBeforeFirst['allowed']);
        
        $invoice1 = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status_id' => $status->id,
        ]);
        $result = $this->limitService->recordUsage($this->user->id, 'invoice', 'count', 'monthly');
        $this->assertTrue($result, 'recordUsage should return true when successful');
        
        // Second invoice creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit($this->user->id, 'invoice', 'count', 'monthly');
        $this->assertFalse($checkBeforeSecond['allowed']);
        $this->assertEquals('limit_exceeded', $checkBeforeSecond['reason']);
        
        // Verify first invoice was created
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice1->id,
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function show_returns_view_with_limits_data(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('frontend.invoice.show', ['locale' => 'en', 'id' => $this->invoice->id]));
        $response->assertOk();
        $response->assertViewIs('frontend.invoices.show');
        $response->assertViewHas('limitsData');
    }

    #[Test]
    public function download_invoice_preview_streams_pdf(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('frontend.invoice.download', ['locale' => 'en', 'id' => $this->invoice->id, 'preview' => 1]));
        // We cannot easily assert PDF stream content, but we assert headers
        $response->assertOk();
        $this->assertTrue(str_contains(strtolower($response->headers->get('content-type') ?? ''), 'pdf'));
    }

    #[Test]
    public function mark_as_paid_updates_status(): void
    {
        $this->actingAs($this->user, 'web');
        // Ensure a 'paid' status exists with slug 'paid'
        $paidStatus = Status::firstOrCreate([
            'slug' => 'paid'
        ], [
            'name' => 'paid',
            'category_id' => \App\Models\StatusCategory::factory()->create()->id,
            'color' => 'bg-green-100 text-green-800',
            'description' => 'Paid status',
            'is_active' => true,
        ]);

        $this->put(route('frontend.invoice.mark-as-paid', ['locale' => 'en', 'id' => $this->invoice->id]));
        $this->invoice->refresh();
        $this->assertEquals($paidStatus->id, $this->invoice->payment_status_id);
    }

    #[Test]
    public function set_template_delegation_keeps_template_value(): void
    {
        $this->actingAs($this->user, 'web');
        $this->put(route('frontend.invoice.set-template', [
            'locale' => 'en', 'id' => $this->invoice->id, 'template' => 'modern'
        ]), ['template' => 'modern']);
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern'
        ]);
    }

    #[Test]
    public function show_view_displays_invoice_and_limits(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('frontend.invoice.show', ['locale' => 'en', 'id' => $this->invoice->id]));
        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_vs);
    }

    #[Test]
    public function download_preview_streams_pdf(): void
    {
        $this->actingAs($this->user, 'web');
        // Mock PDF application service (controller uses InvoicePdfApplicationServiceInterface->generate())
        $pdfMock = $this->mock(\App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class);
        $pdfMock->shouldReceive('generate')
            ->once()
            ->andReturn(\App\Application\Invoice\DTO\InvoiceActionResult::success(
                response: response('PDF CONTENT', 200, ['Content-Type' => 'application/pdf'])
            ));
        $response = $this->get(route('frontend.invoice.download', ['locale' => 'en', 'id' => $this->invoice->id, 'preview' => 1]));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function download_without_preview_sets_attachment_disposition(): void
    {
        $this->actingAs($this->user, 'web');
        $filename = 'faktura-' . $this->invoice->invoice_vs . '.pdf';
        $responseMock = response('PDF CONTENT', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
        $pdfMock = $this->mock(\App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class);
        $pdfMock->shouldReceive('generate')
            ->once()
            ->andReturn(\App\Application\Invoice\DTO\InvoiceActionResult::success(response: $responseMock));
        $response = $this->get(route('frontend.invoice.download', ['locale' => 'en', 'id' => $this->invoice->id]));
        $response->assertOk();
        $this->assertTrue(str_contains(strtolower($response->headers->get('content-disposition') ?? ''), 'attachment')); 
        $this->assertStringContainsString($this->invoice->invoice_vs, $response->headers->get('content-disposition'));
    }
}
