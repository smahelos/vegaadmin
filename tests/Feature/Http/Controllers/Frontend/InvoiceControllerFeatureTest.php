<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Enums\InvoiceTemplate;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\Enums\Permission;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Permission as PermissionModel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\App;
use PHPUnit\Framework\Attributes\Test;
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
            PermissionModel::findByName(Permission::INVOICE_VIEW->value, 'web'),
            PermissionModel::findByName(Permission::INVOICE_CREATE->value, 'web'),
            PermissionModel::findByName(Permission::INVOICE_UPDATE->value, 'web'),
        );

        // A draft: only a draft is edited
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $this->invoice = $draftFactory->create(
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
    public function set_template_of_a_missing_invoice_is_not_found(): void
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

        $response->assertNotFound();
    }

    #[Test]
    public function set_template_of_another_users_invoice_is_not_found(): void
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

        $response->assertNotFound();

        // Verify template was not changed
        $this->assertDatabaseHas('invoices', [
            'id' => $otherInvoice->id,
            'template' => 'default',
        ]);
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
        $invoiceQuery = Invoice::where('user_id', $this->user->id);
        $invoiceQuery->where('invoice_ks', '0308');
        $invoice = $invoiceQuery->sole();
        // A draft until issued: no number, no dates
        $this->assertNull($invoice->issued_at);
        $this->assertNull($invoice->invoice_vs);
        $this->assertNull($invoice->issue_date);
        $this->assertSame('modern', $invoice->template);
        $this->assertSame('cs', $invoice->document_language);
        $this->assertSame('100.00', $invoice->payment_amount);

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $invoice->id]),
            array_replace(
                $data,
                [
                    'invoice_ks' => null,
                    'invoice_text' => null,
                    'document_language' => 'de',
                    'due_in' => 30,
                ],
            ),
        );

        $response->assertSessionHasNoErrors();
        $invoice = $invoice->fresh();
        $this->assertNull($invoice->invoice_ks);
        $this->assertNull($invoice->invoice_text);
        $this->assertSame('modern', $invoice->template);
        $this->assertSame('de', $invoice->document_language);
        $this->assertSame(30, $invoice->due_in);
    }

    #[Test]
    public function save_and_issue_returns_to_the_detail_with_the_issue_dialog_open(): void
    {
        $this->actingAs($this->user, 'web');
        [$business, $account] = $this->issuerOf($this->user);
        $data = $this->invoiceInput($business, $account);
        $this->post(
            route('frontend.invoice.store', ['locale' => 'cs']),
            $data,
        );
        $invoiceQuery = Invoice::where('user_id', $this->user->id);
        $invoiceQuery->whereNull('issued_at');
        $invoiceQuery->latest('id');
        $draft = $invoiceQuery->firstOrFail();

        $response = $this->put(
            route('frontend.invoice.update', ['locale' => 'cs', 'id' => $draft->id]),
            [...$data, 'issue' => '1'],
        );

        $response->assertRedirect(
            route('frontend.invoice.show', [
                'locale' => 'cs',
                'id' => $draft->id,
                'issue' => 1,
            ]),
        );
        $detail = $this->get(
            route('frontend.invoice.show', [
                'locale' => 'cs',
                'id' => $draft->id,
                'issue' => 1,
            ]),
        );
        $detail->assertSee(
            __('invoices.titles.issue'),
        );
        $this->assertNull($draft->fresh()->issued_at);
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
    public function the_status_of_an_issued_invoice_is_set_and_unset(): void
    {
        $issued = $this->issuedInvoice();
        $this->actingAs($this->user, 'web');

        $response = $this->put(
            route(
                'frontend.invoices.set-status',
                [
                    'locale' => 'en',
                    'invoice' => $issued->id,
                    'status' => InvoiceStatus::PAID->value,
                ],
            ),
        );

        $response->assertSessionHas('success');
        $this->assertSame(InvoiceStatus::PAID, $issued->fresh()->payment_status);
        $response = $this->put(
            route(
                'frontend.invoices.set-status',
                [
                    'locale' => 'en',
                    'invoice' => $issued->id,
                    'status' => InvoiceStatus::PENDING->value,
                ],
            ),
        );
        $response->assertSessionHas('success');
        $this->assertSame(InvoiceStatus::PENDING, $issued->fresh()->payment_status);
    }

    #[Test]
    public function a_draft_has_no_payment_status_to_set(): void
    {
        $this->actingAs($this->user, 'web');

        $response = $this->put(
            route(
                'frontend.invoices.set-status',
                [
                    'locale' => 'en',
                    'invoice' => $this->invoice->id,
                    'status' => InvoiceStatus::PAID->value,
                ],
            ),
        );

        $response->assertSessionMissing('success');
        $this->assertSame(InvoiceStatus::PENDING, $this->invoice->fresh()->payment_status);
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
    public function show_view_offers_issuing_a_draft(): void
    {
        $this->user->givePermissionTo(
            PermissionModel::findByName(Permission::INVOICE_DELETE->value, 'web'),
        );
        $this->actingAs($this->user, 'web');
        $response = $this->get(
            route('frontend.invoice.show', ['locale' => 'en', 'id' => $this->invoice->id]),
        );
        // The page renders in the user's language, which the locale middleware has set by now
        $locale = App::getLocale();
        $response->assertStatus(200);
        $response->assertSee(
            __('invoices.titles.draft'),
        );
        // The Livewire snapshot names the component
        $response->assertSee('invoice.issue-invoice-dialog', false);
        $response->assertSee(
            route('frontend.invoices.destroy', ['locale' => $locale, 'invoice' => $this->invoice->id]),
            false,
        );
    }

    #[Test]
    public function show_view_of_an_issued_invoice_offers_only_the_payment_status_and_a_copy(): void
    {
        $issued = $this->issuedInvoice();
        $this->actingAs($this->user, 'web');

        $response = $this->get(
            route('frontend.invoice.show', ['locale' => 'en', 'id' => $issued->id]),
        );

        $locale = App::getLocale();
        $response->assertOk();
        $response->assertSee($issued->invoice_vs);
        $response->assertSee(
            __('invoices.messages.issued_note'),
        );
        $response->assertSee(
            route('frontend.invoices.copy', ['locale' => $locale, 'invoice' => $issued->id]),
            false,
        );
        $response->assertDontSee('invoice.issue-invoice-dialog', false);
        $response->assertDontSee(
            route('frontend.invoice.edit', ['locale' => $locale, 'id' => $issued->id]),
            false,
        );
    }

    #[Test]
    public function editing_an_issued_invoice_redirects_to_its_detail(): void
    {
        $issued = $this->issuedInvoice();
        $this->actingAs($this->user, 'web');

        $response = $this->get(
            route('frontend.invoice.edit', ['locale' => 'en', 'id' => $issued->id]),
        );

        $response->assertRedirect(
            route('frontend.invoice.show', ['locale' => 'en', 'id' => $issued->id]),
        );
    }

    #[Test]
    public function show_view_prints_the_stored_lines_in_the_page_language(): void
    {
        $this->invoice->update(['payment_amount' => '242.00']);
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(
            [
                'invoice_id' => $this->invoice->id,
                'product_id' => null,
                'is_custom_product' => true,
                'name' => 'Consulting',
                'quantity' => '2',
                'unit' => Unit::HOURS->value,
                'price' => '100',
                'currency' => Currency::CZK->value,
                'tax_rate' => '21',
            ],
        );
        $money = app(MoneyFormatterInterface::class);
        $this->actingAs($this->user, 'web');

        $response = $this->get(
            route('frontend.invoice.show', ['locale' => 'en', 'id' => $this->invoice->id]),
        );

        // The page renders in the user's language, which the locale middleware has set by now
        $locale = App::getLocale();
        $response->assertOk();
        $response->assertSee('Consulting');
        $response->assertSee(
            $money->formatWithCode(
                Money::fromString('100', Currency::CZK),
                $locale,
            ),
        );
        $response->assertSee(
            $money->formatWithCode(
                Money::fromString('242', Currency::CZK),
                $locale,
            ),
        );
        // The columns hold "2.000" and "21.00"; the page shows them as numbers of its language
        $response->assertDontSee('2.000');
        $response->assertDontSee('21.00');
        $response->assertSee(
            __(
                'payment_methods.methods.bankaccount',
                [],
                $locale,
            ),
        );
    }

    #[Test]
    public function edit_view_fills_the_form_with_the_stored_invoice_and_line_names(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create(
            ['user_id' => $this->user->id, 'name' => 'Renamed in the catalog'],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(
            [
                'invoice_id' => $this->invoice->id,
                'product_id' => $product->id,
                'name' => 'Name on the invoice',
            ],
        );
        $this->actingAs($this->user, 'web');

        $response = $this->get(
            route('frontend.invoice.edit', ['locale' => 'en', 'id' => $this->invoice->id]),
        );

        $response->assertOk();
        $response->assertViewHas(
            'values',
            fn (array $values): bool => $values['document_language'] === 'cs'
                && !array_key_exists('invoice_vs', $values),
        );
        $response->assertViewHas(
            'items',
            fn (array $items): bool => count($items) === 1
                && $items[0]->name === 'Name on the invoice'
                && $items[0]->productId?->value === $product->id,
        );
        $response->assertSee(
            '{"product_id":' . $product->id . ',"name":"Name on the invoice"',
            false,
        );
    }

    #[Test]
    public function download_without_preview_sets_attachment_disposition(): void
    {
        $issued = $this->issuedInvoice();
        $this->actingAs($this->user, 'web');
        $response = $this->get(
            route('frontend.invoice.download', ['locale' => 'en', 'id' => $issued->id]),
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
            $issued->invoice_vs,
            $response->headers->get('content-disposition'),
        );
    }

    #[Test]
    public function store_refuses_to_save_a_customer_whose_company_number_is_in_the_contacts(): void
    {
        $this->user->givePermissionTo(Permission::CONTACT_CREATE);
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
        $invoiceQuery = Invoice::where('user_id', $this->user->id);
        $invoiceQuery->whereKeyNot($this->invoice->id);
        $this->assertFalse($invoiceQuery->exists());
        $contactQuery = Contact::where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $contactQuery->count(),
        );
    }

    private function issuedInvoice(): Invoice
    {
        $invoiceFactory = Invoice::factory();

        return $invoiceFactory->create(['user_id' => $this->user->id]);
    }
}
