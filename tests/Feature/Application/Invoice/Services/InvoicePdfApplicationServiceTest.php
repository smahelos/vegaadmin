<?php

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoicePdfApplicationServiceTest extends TestCase
{
    use AssertsInvoiceActionResult;

    private InvoicePdfApplicationServiceInterface $pdfService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_invoice', 'guard_name' => 'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
        // Register fake limit service BEFORE resolving application services
        $fakeLimit = new class implements UniversalLimitServiceInterface
        {
            public function checkLimit(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                string $periodType = 'daily',
                int|float $value = 1,
            ): array {
                return [
                    'allowed' => true,
                    'current_usage' => 0,
                    'limit' => 50,
                    'remaining' => 50,
                    'entity_type' => $entityType,
                    'metric_type' => $metricType,
                    'period_type' => $periodType,
                    'reason' => 'within_limit',
                ];
            }

            public function recordUsage(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                ?string $periodType = 'daily',
                string $guard = 'web',
                int|float $value = 1,
            ): bool {
                return true;
            }

            public function resetUsage(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                ?string $periodType = 'daily',
            ): bool {
                return true;
            }

            public function getUsageStatistics(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                ?string $periodType = 'daily',
            ): array {
                return [
                    'current_usage' => 0,
                    'limit' => 50,
                    'allowed' => true,
                ];
            }

            public function getBestPeriodType(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
            ): string {
                return 'daily';
            }

            public function getEntityLimitInfo(?int $userId, string $entityType): array
            {
                return [];
            }

            public function canUserCreateEntity(?int $userId, string $entityType): bool
            {
                return true;
            }
        };
        $this->app->instance(UniversalLimitServiceInterface::class, $fakeLimit);
        // Now resolve services
        $this->pdfService = app(InvoicePdfApplicationServiceInterface::class);
    }

    /**
     * Validated input of a guest invoice, as the form request hands it over.
     *
     * @return array<string, mixed>
     */
    private function guestInput(): array
    {
        return [
            'invoice_vs' => 'INV-' . uniqid(),
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id,
            'payment_currency' => 'EUR',
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->format('Y-m-d'),
            'due_in' => 14,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail()->id,
            'invoice_text' => 'Test invoice',
            'invoice-products' => [
                [
                    'name' => 'Service A',
                    'quantity' => '2',
                    'unit' => 'pieces',
                    'price' => '50',
                    'currency' => 'EUR',
                    'tax_rate' => '21',
                ],
            ],
        ];
    }

    private function party(string $name): DocumentPartyDTO
    {
        return new DocumentPartyDTO(
            $name,
            null,
            null,
            Address::fromParts(
                'Main 1',
                'Brno',
                '602 00',
                Country::CZ,
            ),
            null,
            null,
        );
    }

    #[Test]
    public function user_can_generate_pdf(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id, 'payment_currency' => 'EUR']);
        InvoiceProduct::factory()->create(['invoice_id' => $invoice->id, 'currency' => 'EUR']);

        $pdf = $this->pdfService->generate($this->user->id, $invoice->id);

        $this->assertInvoiceSuccess($pdf, 'PDF generation failed');
        $this->assertNotNull($pdf->response);
        $this->assertEquals(InvoiceActionStatus::SUCCESS, $pdf->status);
    }

    #[Test]
    public function guest_can_preview_pdf_by_token(): void
    {
        $stored = app(GuestInvoiceApplicationServiceInterface::class)->storeGuestInvoice(
            $this->guestInput(),
            $this->party('Dodavatel s.r.o.'),
            $this->party('Odběratel s.r.o.'),
            null,
            'en',
        );

        $result = $this->pdfService->generate(
            userId: null,
            token: $stored['token'],
            locale: 'en',
            preview: true,
        );

        $this->assertInvoiceSuccess($result, 'Guest preview failed');
        $this->assertNotNull($result->response);
    }

    #[Test]
    public function another_users_invoice_is_not_rendered(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();

        $pdf = $this->pdfService->generate($otherUser->id, $invoice->id);

        $this->assertInvoiceFailure($pdf);
        $this->assertSame('invoices.messages.show_error', $pdf->message);
        $this->assertNull($pdf->response);
    }

    #[Test]
    public function guest_without_a_token_gets_not_found(): void
    {
        $result = $this->pdfService->generate(
            userId: null,
            locale: 'cs',
        );

        $this->assertInvoiceFailure($result);
        $this->assertSame('invoices.messages.not_found', $result->message);
    }

    #[Test]
    public function guest_with_an_unknown_token_gets_expired(): void
    {
        $result = $this->pdfService->generate(
            userId: null,
            token: 'unknown-token',
            locale: 'cs',
        );

        $this->assertInvoiceFailure($result);
        $this->assertSame('invoices.messages.expired', $result->message);
    }
}
