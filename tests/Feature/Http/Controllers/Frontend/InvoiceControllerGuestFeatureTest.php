<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;

class InvoiceControllerGuestFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        // guest scenarios do not authenticate user
        $this->setUpFrontendTestEnvironment();
    }

    #[Test]
    public function create_for_guest_returns_view_with_user_logged_in_false(): void
    {
        $response = $this->get(
            route('frontend.invoice.create.guest', ['locale' => 'cs']),
        );
        $response->assertOk();
        $response->assertViewIs('frontend.invoices.create');
        $this->assertTrue(
            $response->viewData('userLoggedIn') === false,
        );
    }

    #[Test]
    public function store_guest_validation_fails_with_missing_required_fields(): void
    {
        // Intentionally send only lang - validation should fail before controller logic executes
        $response = $this->postJson(
            route('frontend.invoice.store.guest', ['locale' => 'cs']),
            ['lang' => 'cs'],
        );
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        // Check a representative subset of required fields
        $response->assertJsonValidationErrors(
            [
                'invoice_vs',
                'payment_method_id',
                'payment_currency',
                'issue_date',
                'due_in',
                'status',
                'template',
                'invoice-products',
            ],
        );
    }

    #[Test]
    public function store_guest_stores_the_invoice_and_its_token_downloads_the_pdf(): void
    {
        // Provide full valid payload to satisfy validation (guest create)
        $paymentMethodQuery = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $paymentMethodId = $paymentMethodQuery->firstOrFail()->id;
        $now = CarbonImmutable::now();
        $invoiceData = [
            'invoice_vs' => $now->format('Y') . '0001',
            'payment_method_id' => $paymentMethodId,
            'payment_amount' => 100,
            'payment_currency' => 'CZK',
            'invoice-products' => json_encode(
                [[
                    'name' => 'Service',
                    'quantity' => '1',
                    'unit' => 'pieces',
                    'price' => '100',
                    'currency' => 'CZK',
                    'tax_rate' => '0',
                ]],
            ),
            'issue_date' => $now->format('Y-m-d'),
            'tax_point_date' => $now->format('Y-m-d'),
            'due_in' => 14,
            'status' => InvoiceStatus::PENDING->value,
            'template' => 'default',
            'supplier_name' => 'Supplier Test',
            'supplier_street' => 'Street 1',
            'supplier_city' => 'City',
            'supplier_postal_code' => '12345',
            'supplier_country' => 'CZ',
            'customer_name' => 'Client Test',
            'customer_street' => 'CStreet 1',
            'customer_city' => 'CCity',
            'customer_postal_code' => '54321',
            'customer_country' => 'CZ',
            'account_iban' => 'CZ1520100000002001234561',
            'account_country' => 'CZ',
        ];
        $payload = array_merge($invoiceData, ['lang' => 'cs']);
        $response = $this->postJson(
            route('frontend.invoice.store.guest', ['locale' => 'cs']),
            $payload,
            ['Accept' => 'application/json'],
        );
        $response->assertOk();
        $response->assertJson(
            ['success' => true, 'invoice_number' => $invoiceData['invoice_vs']],
        );
        $response->assertSessionHas(
            'last_guest_invoice_token',
            $response->json('token'),
        );

        $download = $this->get(
            $response->json('download_url') . '?lang=xyz',
        );

        $download->assertOk();
        $download->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $download->getContent());
        // An unsupported requested language renders and is remembered in the current one
        $download->assertCookie('locale', 'cs');
    }

    #[Test]
    public function download_with_an_unknown_token_is_not_found(): void
    {
        $response = $this->get(
            route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'unknown-token']),
        );

        $response->assertNotFound();
    }

    #[Test]
    public function guest_deletes_the_invoice_of_their_session(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $httpClient = $this->withSession(['last_guest_invoice_token' => $token]);
        $response = $httpClient->delete(
            route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]),
        );

        $response->assertRedirect(
            route('home', ['locale' => 'cs']),
        );
        $response->assertSessionHas('success');
        $response->assertSessionMissing('last_guest_invoice_token');
        $this->assertNull(
            $store->get($token),
        );
    }

    #[Test]
    public function guest_cannot_delete_an_invoice_of_another_session(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $httpClient = $this->withSession(
            ['last_guest_invoice_token' => str_repeat('a', 64)],
        );
        $response = $httpClient->delete(
            route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]),
        );

        $response->assertRedirect(
            route('home', ['locale' => 'cs']),
        );
        $response->assertSessionHas('error');
        $this->assertNotNull(
            $store->get($token),
        );
    }

    #[Test]
    public function guest_invoice_is_not_deleted_over_get(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $httpClient = $this->withSession(['last_guest_invoice_token' => $token]);
        $httpResponse = $httpClient->get(
            route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]),
        );
        $httpResponse->assertMethodNotAllowed();

        $this->assertNotNull(
            $store->get($token),
        );
    }
}
