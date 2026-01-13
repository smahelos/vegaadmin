<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Application\Invoice\DTO\InvoiceMutationPayload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Spatie\Permission\Models\Permission;

class InvoiceControllerGuestFeatureTest extends TestCase
{
    use RefreshDatabase, CreatesFrontendTestEnvironment;

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
        $response->assertJsonStructure(['message','errors']);
        // Check a representative subset of required fields
        $response->assertJsonValidationErrors([
            'invoice_vs','payment_method_id','payment_amount','payment_currency','issue_date','due_in','payment_status_id'
        ]);
    }

    #[Test]
    public function store_guest_creates_invoice_and_returns_json_success(): void
    {
        // Provide full valid payload to satisfy validation (guest create)
        $paymentMethodId = \App\Models\PaymentMethod::factory()->create()->id;
        $statusId = \App\Models\Status::factory()->create()->id;
        $invoiceData = [
            'invoice_vs' => 'GUEST-'.uniqid(),
            'payment_method_id' => $paymentMethodId,
            'payment_amount' => 100,
            'payment_currency' => 'CZK',
            'issue_date' => now()->format('Y-m-d'),
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
        $this->mock(\App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface::class)
            ->shouldReceive('assemble')
            ->once()
            ->andReturn(new InvoiceMutationPayload($invoiceData, []));

        $this->mock(\App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('storeGuestInvoice')
            ->once()
            ->andReturn([
                'success' => true,
                'token' => 'tok_' . uniqid(),
                'message' => __('invoices.messages.created')
            ]);

        $payload = array_merge($invoiceData, [ 'lang' => 'cs' ]);
        $response = $this->postJson(route('frontend.invoice.store.guest', ['locale' => 'cs']), $payload, [ 'Accept' => 'application/json' ]);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function store_guest_handles_exception_and_returns_error_json(): void
    {
        // Provide full valid payload to satisfy validation then simulate exception in service
        $paymentMethodId = \App\Models\PaymentMethod::factory()->create()->id;
        $statusId = \App\Models\Status::factory()->create()->id;
        $invoiceData = [
            'invoice_vs' => 'GUEST-'.uniqid(),
            'payment_method_id' => $paymentMethodId,
            'payment_amount' => 50,
            'payment_currency' => 'CZK',
            'issue_date' => now()->format('Y-m-d'),
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
            'client_country' => 'CZ'
        ];
        $this->mock(\App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface::class)
            ->shouldReceive('assemble')
            ->once()
            ->andReturn(new InvoiceMutationPayload($invoiceData, []));
        $this->mock(\App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class)
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
        $pdfResponse = response('PDF', 200, ['Content-Type' => 'application/pdf']);
        $this->mock(\App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class)
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
        app()->bind(\App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class, function() {
            return new class implements \App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface {
                public function generateForUser(?int $userId, int $invoiceId, ?string $locale = null, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure(); }
                public function generateForGuestByToken(?string $token, ?string $requestedLocale, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure('invoices.messages.not_found'); }
                public function generate(?int $userId, ?int $invoiceId = null, ?string $token = null, ?string $locale = null, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure('invoices.messages.not_found'); }
            }; });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'tokXXX']));
    }

    #[Test]
    public function download_with_token_generic_failure_returns_500(): void
    {
        $this->withoutExceptionHandling();
        app()->bind(\App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface::class, function() {
            return new class implements \App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface {
                public function generateForUser(?int $userId, int $invoiceId, ?string $locale = null, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure(); }
                public function generateForGuestByToken(?string $token, ?string $requestedLocale, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure('invoices.messages.pdf_error'); }
                public function generate(?int $userId, ?int $invoiceId = null, ?string $token = null, ?string $locale = null, bool $preview = false): InvoiceActionResult { return InvoiceActionResult::failure('invoices.messages.pdf_error'); }
            }; });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->get(route('frontend.invoice.download.token', ['locale' => 'cs', 'token' => 'tokERR']));
    }

    #[Test]
    public function delete_guest_invoice_success_redirects_with_success(): void
    {
        $this->mock(\App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('deleteTemporary')
            ->once()
            ->andReturn(true);

        $response = $this->get(route('frontend.invoice.delete.token', ['locale' => 'cs', 'token' => 'tokDEL']));
        $response->assertRedirect(route('home', ['locale' => 'cs']));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function delete_guest_invoice_failure_redirects_with_error(): void
    {
        $this->mock(\App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('deleteTemporary')
            ->once()
            ->andReturn(false);

        $response = $this->get(route('frontend.invoice.delete.token', ['locale' => 'cs', 'token' => 'tokBAD']));
        $response->assertRedirect(route('home', ['locale' => 'cs']));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function delete_guest_invoice_exception_redirects_with_error(): void
    {
        $this->mock(\App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface::class)
            ->shouldReceive('deleteTemporary')
            ->once()
            ->andThrow(new \Exception('fail'));

        $response = $this->get(route('frontend.invoice.delete.token', ['locale' => 'cs', 'token' => 'tokEX']));
        $response->assertRedirect(route('home', ['locale' => 'cs']));
        $response->assertSessionHas('error');
    }
}
