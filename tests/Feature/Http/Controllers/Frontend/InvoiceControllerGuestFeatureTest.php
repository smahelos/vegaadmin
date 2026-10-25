<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $response = $this->get(route('frontend.invoice.create.guest', ['locale' => 'cs']));
        $response->assertOk();
        $response->assertViewIs('frontend.invoices.create');
        $this->assertTrue($response->viewData('userLoggedIn') === false);
    }

    #[Test]
    public function store_guest_validation_fails_with_missing_required_fields(): void
    {
        // Intentionally send only lang - validation should fail before controller logic executes
        $response = $this->postJson(route('frontend.invoice.store.guest', ['locale' => 'cs']), ['lang' => 'cs']);
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        // Check a representative subset of required fields
        $response->assertJsonValidationErrors([
            'invoice_vs',
            'payment_method_id',
            'payment_currency',
            'issue_date',
            'due_in',
            'payment_status_id',
            'invoice-products',
        ]);
    }

    #[Test]
    public function store_guest_creates_invoice_and_returns_json_success(): void
    {
        // Provide full valid payload to satisfy validation (guest create)
        $paymentMethodId = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id;
        $statusId = Status::factory()->create()->id;
        $invoiceData = [
            'invoice_vs' => 'GUEST-' . uniqid(),
            'payment_method_id' => $paymentMethodId,
            'payment_amount' => 100,
            'payment_currency' => 'CZK',
            'invoice-products' => json_encode([[
                'name' => 'Service',
                'quantity' => '1',
                'unit' => 'pieces',
                'price' => '100',
                'currency' => 'CZK',
                'tax_rate' => '0',
            ]]),
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->format('Y-m-d'),
            'due_in' => 14,
            'payment_status_id' => $statusId,
            'name' => 'Supplier Test',
            'street' => 'Street 1',
            'city' => 'City',
            'zip' => '12345',
            'country' => 'CZ',
            'client_name' => 'Client Test',
            'client_street' => 'CStreet 1',
            'client_city' => 'CCity',
            'client_zip' => '54321',
            'client_country' => 'CZ',
        ];
        // Mock assembler to return payload as validated
        $this->mock(InvoiceRequestAssemblerInterface::class)
            ->shouldReceive('assemble')
            ->once()
            ->andReturn(new InvoiceMutationPayload($invoiceData, []));

        $this->mock(GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('storeGuestInvoice')
            ->once()
            ->andReturn([
                'success' => true,
                'token' => 'tok_' . uniqid(),
                'message' => __('invoices.messages.created'),
            ]);

        $payload = array_merge($invoiceData, ['lang' => 'cs']);
        $response = $this->postJson(
            route('frontend.invoice.store.guest', ['locale' => 'cs']),
            $payload,
            ['Accept' => 'application/json'],
        );
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function store_guest_handles_exception_and_returns_error_json(): void
    {
        // Provide full valid payload to satisfy validation then simulate exception in service
        $paymentMethodId = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id;
        $statusId = Status::factory()->create()->id;
        $invoiceData = [
            'invoice_vs' => 'GUEST-' . uniqid(),
            'payment_method_id' => $paymentMethodId,
            'payment_amount' => 50,
            'payment_currency' => 'CZK',
            'invoice-products' => json_encode([[
                'name' => 'Service',
                'quantity' => '1',
                'unit' => 'pieces',
                'price' => '100',
                'currency' => 'CZK',
                'tax_rate' => '0',
            ]]),
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->format('Y-m-d'),
            'due_in' => 7,
            'payment_status_id' => $statusId,
            'name' => 'Supp X',
            'street' => 'S',
            'city' => 'C',
            'zip' => '11111',
            'country' => 'CZ',
            'client_name' => 'Client X',
            'client_street' => 'CS',
            'client_city' => 'CC',
            'client_zip' => '22222',
            'client_country' => 'CZ',
        ];
        $this->mock(InvoiceRequestAssemblerInterface::class)
            ->shouldReceive('assemble')
            ->once()
            ->andReturn(new InvoiceMutationPayload($invoiceData, []));
        $this->mock(GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('storeGuestInvoice')
            ->once()
            ->andThrow(new \Exception('boom'));
        $response = $this->postJson(route('frontend.invoice.store.guest', ['locale' => 'cs']), array_merge($invoiceData, ['lang' => 'cs']));
        $response->assertStatus(500)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function download_with_token_streams_pdf(): void
    {
        $pdfResponse = response(
            'PDF',
            200,
            ['Content-Type' => 'application/pdf'],
        );
        $this->mock(InvoicePdfApplicationServiceInterface::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn(InvoiceActionResult::success(response: $pdfResponse));

        $response = $this->get(route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'tok123']));
        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function download_with_token_not_found_returns_404(): void
    {
        $this->withoutExceptionHandling();
        app()->bind(InvoicePdfApplicationServiceInterface::class, function () {
            return new class implements InvoicePdfApplicationServiceInterface
            {
                public function generateForUser(
                    ?int $userId,
                    int $invoiceId,
                    ?string $locale = null,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure();
                }

                public function generateForGuestByToken(
                    ?string $token,
                    ?string $requestedLocale,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure('invoices.messages.not_found');
                }

                public function generate(
                    ?int $userId,
                    ?int $invoiceId = null,
                    ?string $token = null,
                    ?string $locale = null,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure('invoices.messages.not_found');
                }
            };
        });
        $this->expectException(NotFoundHttpException::class);
        $this->get(route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'tokXXX']));
    }

    #[Test]
    public function download_with_token_generic_failure_returns_500(): void
    {
        $this->withoutExceptionHandling();
        app()->bind(InvoicePdfApplicationServiceInterface::class, function () {
            return new class implements InvoicePdfApplicationServiceInterface
            {
                public function generateForUser(
                    ?int $userId,
                    int $invoiceId,
                    ?string $locale = null,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure();
                }

                public function generateForGuestByToken(
                    ?string $token,
                    ?string $requestedLocale,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure('invoices.messages.pdf_error');
                }

                public function generate(
                    ?int $userId,
                    ?int $invoiceId = null,
                    ?string $token = null,
                    ?string $locale = null,
                    bool $preview = false,
                ): InvoiceActionResult {
                    return InvoiceActionResult::failure('invoices.messages.pdf_error');
                }
            };
        });
        $this->expectException(HttpException::class);
        $this->get(route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'tokERR']));
    }

    #[Test]
    public function guest_deletes_the_invoice_of_their_session(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $response = $this->withSession(['last_guest_invoice_token' => $token])
            ->delete(route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]));

        $response->assertRedirect(route('home', ['locale' => 'cs']));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('last_guest_invoice_token');
        $this->assertNull($store->get($token));
    }

    #[Test]
    public function guest_cannot_delete_an_invoice_of_another_session(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $response = $this->withSession(['last_guest_invoice_token' => str_repeat('a', 64)])
            ->delete(route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]));

        $response->assertRedirect(route('home', ['locale' => 'cs']));
        $response->assertSessionHas('error');
        $this->assertNotNull($store->get($token));
    }

    #[Test]
    public function guest_invoice_is_not_deleted_over_get(): void
    {
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store(['invoice_vs' => '20260001']);

        $this->withSession(['last_guest_invoice_token' => $token])
            ->get(route('frontend.guest-invoices.destroy', ['locale' => 'cs', 'token' => $token]))
            ->assertMethodNotAllowed();

        $this->assertNotNull($store->get($token));
    }
}
