<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\Contact;
use App\Models\EntityLimit;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class InvoiceControllerFeatureTest extends TestCase
{
    use BuildsInvoiceInput, CreatesFrontendTestEnvironment;

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
            'template' => 'default',
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
            'template' => 'modern',
        ]), [
            'template' => 'modern',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify database was updated
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]);
    }

    #[Test]
    public function set_template_uses_request_template_over_route_parameter(): void
    {
        $this->actingAs($this->user, 'web');

        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'minimal',
        ]), [
            'template' => 'modern',  // Request parameter should take precedence
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify request parameter was used
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]);
    }

    #[Test]
    public function set_template_defaults_to_default_template(): void
    {
        $this->actingAs($this->user, 'web');

        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'default',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify default template was set
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'default',
        ]);
    }

    #[Test]
    public function set_template_fails_for_unauthenticated_user(): void
    {
        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]), [
            'template' => 'modern',
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
            'template' => 'modern',
        ]), [
            'template' => 'modern',
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
            'template' => 'default',
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $otherInvoice->id,
            'template' => 'modern',
        ]), [
            'template' => 'modern',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Verify template was not changed
        $this->assertDatabaseHas('invoices', [
            'id' => $otherInvoice->id,
            'template' => 'default',
        ]);
    }

    #[Test]
    public function set_template_handles_service_exception(): void
    {
        $this->actingAs($this->user, 'web');

        // Mock the service to throw an exception
        $mockService = $this->mock(InvoiceServiceInterface::class);
        $mockService->shouldReceive('setInvoiceTemplate')
            ->with($this->invoice->id, 'modern')
            ->andThrow(new \Exception('Service error'));

        $response = $this->putJson(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]), [
            'template' => 'modern',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function set_template_accepts_valid_template_names(): void
    {
        $this->actingAs($this->user, 'web');

        $validTemplates = [
            'default',
            'modern',
            'minimal',
        ];

        foreach ($validTemplates as $template) {
            $response = $this->putJson(route('frontend.invoice.set-template', [
                'locale' => 'en',
                'id' => $this->invoice->id,
                'template' => $template,
            ]), [
                'template' => $template,
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('invoices', [
                'id' => $this->invoice->id,
                'template' => $template,
            ]);
        }
    }

    #[Test]
    public function set_template_route_exists_and_has_correct_parameters(): void
    {
        $route = route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => 1,
            'template' => 'modern',
        ]);

        $this->assertStringContainsString('/invoice/1/set-template/modern', $route);
    }

    #[Test]
    public function invoice_creation_records_entity_usage(): void
    {
        $this->actingAs($this->user, 'web');

        [$business, $account] = $this->issuerOf($this->user);

        // Check initial usage
        $initialCheck = $this->limitService->checkLimit(
            $this->user->id,
            'invoice',
            'count',
            'monthly',
        );
        $initialUsage = $initialCheck['current_usage'] ?? 0;

        $invoiceData = $this->invoiceInput($business, $account);

        // Act
        $response = $this->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData);

        // Assert
        $response->assertRedirect(route('frontend.invoices', ['locale' => 'cs']));
        $response->assertSessionHas('success');

        // Check usage after creation
        $newCheck = $this->limitService->checkLimit(
            $this->user->id,
            'invoice',
            'count',
            'monthly',
        );
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
            'permission_name' => 'frontend.can_create_edit_invoice',
        ])->update(['limit_value' => 1]);

        // First invoice creation - should succeed
        $checkBeforeFirst = $this->limitService->checkLimit(
            $this->user->id,
            'invoice',
            'count',
            'monthly',
        );
        $this->assertTrue($checkBeforeFirst['allowed']);

        $invoice1 = Invoice::factory()->create(['user_id' => $this->user->id]);
        $result = $this->limitService->recordUsage(
            $this->user->id,
            'invoice',
            'count',
            'monthly',
        );
        $this->assertTrue($result, 'recordUsage should return true when successful');

        // Second invoice creation - should fail due to limit
        $checkBeforeSecond = $this->limitService->checkLimit(
            $this->user->id,
            'invoice',
            'count',
            'monthly',
        );
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
        $response = $this->get(route('frontend.invoice.download', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'preview' => 1,
        ]));
        // We cannot easily assert PDF stream content, but we assert headers
        $response->assertOk();
        $this->assertTrue(str_contains(strtolower($response->headers->get('content-type') ?? ''), 'pdf'));
    }

    #[Test]
    public function mark_as_paid_updates_status(): void
    {
        $this->actingAs($this->user, 'web');
        $paidStatus = Status::where('slug', 'paid')->firstOrFail();

        $this->put(route('frontend.invoice.mark-as-paid', ['locale' => 'en', 'id' => $this->invoice->id]));
        $this->invoice->refresh();
        $this->assertEquals($paidStatus->id, $this->invoice->payment_status_id);
    }

    #[Test]
    public function set_template_delegation_keeps_template_value(): void
    {
        $this->actingAs($this->user, 'web');
        $this->put(route('frontend.invoice.set-template', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]), ['template' => 'modern']);
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern',
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
        $pdfMock = $this->mock(InvoicePdfApplicationServiceInterface::class);
        $pdfMock->shouldReceive('generate')
            ->once()
            ->andReturn(InvoiceActionResult::success(
                response: response(
                    'PDF CONTENT',
                    200,
                    ['Content-Type' => 'application/pdf'],
                ),
            ));
        $response = $this->get(route('frontend.invoice.download', [
            'locale' => 'en',
            'id' => $this->invoice->id,
            'preview' => 1,
        ]));
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function download_without_preview_sets_attachment_disposition(): void
    {
        $this->actingAs($this->user, 'web');
        $filename = 'faktura-' . $this->invoice->invoice_vs . '.pdf';
        $responseMock = response(
            'PDF CONTENT',
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        );
        $pdfMock = $this->mock(InvoicePdfApplicationServiceInterface::class);
        $pdfMock->shouldReceive('generate')
            ->once()
            ->andReturn(InvoiceActionResult::success(response: $responseMock));
        $response = $this->get(route('frontend.invoice.download', ['locale' => 'en', 'id' => $this->invoice->id]));
        $response->assertOk();
        $this->assertTrue(str_contains(strtolower($response->headers->get('content-disposition') ?? ''), 'attachment'));
        $this->assertStringContainsString($this->invoice->invoice_vs, $response->headers->get('content-disposition'));
    }

    #[Test]
    public function store_refuses_to_save_a_customer_whose_company_number_is_in_the_contacts(): void
    {
        $this->user->givePermissionTo(['frontend.can_create_contact', 'limit_active_contacts_basic']);
        $existing = Contact::factory()->create(['user_id' => $this->user->id, 'company_number' => '27082440']);
        [$business, $account] = $this->issuerOf($this->user);
        $invoiceData = $this->invoiceInput(
            $business,
            $account,
            [
                'customer_registration_country' => 'CZ',
                'customer_company_number' => $existing->company_number,
                'save_customer_to_contacts' => '1',
            ],
        );

        $this->actingAs($this->user, 'web')
            ->post(route('frontend.invoice.store', ['locale' => 'cs']), $invoiceData)
            ->assertSessionHasErrors('customer_company_number');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $invoiceData['invoice_vs']]);
        $this->assertSame(1, Contact::where('user_id', $this->user->id)->count());
    }
}
