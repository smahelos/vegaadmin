<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Models\Contact;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpFrontendTestEnvironment();

        $this->user->givePermissionTo(
            Permission::findByName('frontend.can_create_invoice', 'web'),
            Permission::findByName('frontend.can_update_invoice', 'web'),
        );

        // Create test invoice
        $invoiceFactory = Invoice::factory();
        $this->invoice = $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'template' => 'default',
            ],
        );
    }

    #[Test]
    public function set_template_updates_invoice_template_successfully(): void
    {
        $this->actingAs($this->user, 'web');

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'modern',
                ],
            ),
            [
                'template' => 'modern',
            ],
        );

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

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'minimal',
                ],
            ),
            [
                'template' => 'modern',  // Request parameter should take precedence
            ],
        );

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

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'default',
                ],
            ),
        );

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
        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'modern',
                ],
            ),
            [
                'template' => 'modern',
            ],
        );

        $response->assertStatus(401);
    }

    #[Test]
    public function set_template_fails_for_non_existent_invoice(): void
    {
        $this->actingAs($this->user, 'web');

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => 99999,
                    'template' => 'modern',
                ],
            ),
            [
                'template' => 'modern',
            ],
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function set_template_fails_for_other_users_invoice(): void
    {
        // Create another user and their invoice
        $userFactory = User::factory();
        $otherUser = $userFactory->create();
        $invoiceFactory = Invoice::factory();
        $otherInvoice = $invoiceFactory->create([
            'user_id' => $otherUser->id,
            'template' => 'default',
        ]);

        $this->actingAs($this->user, 'web');

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $otherInvoice->id,
                    'template' => 'modern',
                ],
            ),
            [
                'template' => 'modern',
            ],
        );

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
        $expectation = $mockService->shouldReceive('setInvoiceTemplate');
        $expectation->with($this->invoice->id, 'modern');
        $expectation->andThrow(
            new \Exception('Service error'),
        );

        $response = $this->putJson(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'modern',
                ],
            ),
            [
                'template' => 'modern',
            ],
        );

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
            $response = $this->putJson(
                route(
                    'frontend.invoice.set-template',
                    [
                        'locale' => 'en',
                        'id' => $this->invoice->id,
                        'template' => $template,
                    ],
                ),
                [
                    'template' => $template,
                ],
            );

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
    public function the_form_creates_an_invoice_and_an_edit_replaces_everything_it_sent(): void
    {
        $this->actingAs($this->user, 'web');
        [$business, $account] = $this->issuerOf($this->user);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'invoice_ks' => '0308',
                'invoice_text' => 'Thank you',
                'template' => InvoiceTemplate::MODERN->value,
            ],
        );

        $response = $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $data,
        );

        $response->assertSessionHas('success');
        $invoiceQuery = Invoice::where('invoice_vs', $data['invoice_vs']);
        $invoice = $invoiceQuery->sole();
        $this->assertSame('0308', $invoice->invoice_ks);
        $this->assertSame('modern', $invoice->template);
        $this->assertSame(InvoiceStatus::PENDING->value, $invoice->paymentStatus->slug);
        $this->assertSame('100.00', $invoice->payment_amount);

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            array_replace(
                $data,
                [
                    'invoice_ks' => null,
                    'invoice_text' => null,
                    'status' => InvoiceStatus::PAID->value,
                    'issue_date' => '2026-01-15',
                    'due_in' => 30,
                ],
            ),
        );

        $response->assertSessionHasNoErrors();
        $invoice = $invoice->fresh();
        $this->assertNull($invoice->invoice_ks);
        $this->assertNull($invoice->invoice_text);
        $this->assertSame('modern', $invoice->template);
        $this->assertSame(InvoiceStatus::PAID->value, $invoice->paymentStatus->slug);
        $this->assertSame(
            '2026-01-15',
            $invoice->issue_date->format('Y-m-d'),
        );
        $this->assertSame(30, $invoice->due_in);
    }

    #[Test]
    public function download_invoice_preview_streams_pdf(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(
            route(
                'frontend.invoice.download',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'preview' => 1,
                ],
            ),
        );
        // We cannot easily assert PDF stream content, but we assert headers
        $response->assertOk();
        $this->assertTrue(
            str_contains(
                strtolower(
                    $response->headers->get('content-type') ?? '',
                ),
                'pdf',
            ),
        );
    }

    #[Test]
    public function mark_as_paid_updates_status(): void
    {
        $this->actingAs($this->user, 'web');
        $statusQuery = Status::where('slug', 'paid');
        $paidStatus = $statusQuery->firstOrFail();

        $this->put(
            route('frontend.invoice.mark-as-paid', ['locale' => 'en', 'id' => $this->invoice->id]),
        );
        $this->invoice->refresh();
        $this->assertEquals($paidStatus->id, $this->invoice->payment_status_id);
    }

    #[Test]
    public function set_template_delegation_keeps_template_value(): void
    {
        $this->actingAs($this->user, 'web');
        $this->put(
            route(
                'frontend.invoice.set-template',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'template' => 'modern',
                ],
            ),
            ['template' => 'modern'],
        );
        $this->assertDatabaseHas('invoices', [
            'id' => $this->invoice->id,
            'template' => 'modern',
        ]);
    }

    #[Test]
    public function show_view_displays_the_invoice(): void
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(
            route('frontend.invoice.show', ['locale' => 'en', 'id' => $this->invoice->id]),
        );
        $response->assertStatus(200);
        $response->assertSee($this->invoice->invoice_vs);
    }

    #[Test]
    public function download_preview_streams_pdf(): void
    {
        $this->actingAs($this->user, 'web');
        // Mock PDF application service (controller uses InvoicePdfApplicationServiceInterface->generate())
        $pdfMock = $this->mock(InvoicePdfApplicationServiceInterface::class);
        $expectation = $pdfMock->shouldReceive('generate');
        $expectation->once();
        $expectation->andReturn(
            InvoiceActionResult::success(
                response: response(
                    'PDF CONTENT',
                    200,
                    ['Content-Type' => 'application/pdf'],
                ),
            ),
        );
        $response = $this->get(
            route(
                'frontend.invoice.download',
                [
                    'locale' => 'en',
                    'id' => $this->invoice->id,
                    'preview' => 1,
                ],
            ),
        );
        $response->assertStatus(200);
        $this->assertEquals(
            'application/pdf',
            $response->headers->get('Content-Type'),
        );
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
        $expectation = $pdfMock->shouldReceive('generate');
        $expectation->once();
        $expectation->andReturn(
            InvoiceActionResult::success(response: $responseMock),
        );
        $response = $this->get(
            route('frontend.invoice.download', ['locale' => 'en', 'id' => $this->invoice->id]),
        );
        $response->assertOk();
        $this->assertTrue(
            str_contains(
                strtolower(
                    $response->headers->get('content-disposition') ?? '',
                ),
                'attachment',
            ),
        );
        $this->assertStringContainsString(
            $this->invoice->invoice_vs,
            $response->headers->get('content-disposition'),
        );
    }

    #[Test]
    public function store_refuses_to_save_a_customer_whose_company_number_is_in_the_contacts(): void
    {
        $this->user->givePermissionTo('frontend.can_create_contact');
        $contactFactory = Contact::factory();
        $existing = $contactFactory->create(
            ['user_id' => $this->user->id, 'company_number' => '27082440'],
        );
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

        $httpClient = $this->actingAs($this->user, 'web');
        $httpResponse = $httpClient->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $invoiceData,
        );
        $httpResponse->assertSessionHasErrors('customer_company_number');
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $invoiceData['invoice_vs']]);
        $contactQuery = Contact::where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $contactQuery->count(),
        );
    }
}
