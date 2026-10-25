<?php

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
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

    private InvoiceMutationApplicationServiceInterface $mutationService;

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
        $this->mutationService = app(InvoiceMutationApplicationServiceInterface::class);
    }

    private function baseData(): array
    {
        return [
            'invoice_vs' => 'INV-' . uniqid(),
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id,
            'payment_amount' => 100,
            'payment_currency' => 'EUR',
            'issue_date' => now()->format('Y-m-d'),
            'tax_point_date' => now()->format('Y-m-d'),
            'due_in' => 14,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail()->id,
            'name' => 'Supplier ' . uniqid(),
            'street' => 'Main',
            'city' => 'City',
            'zip' => '12345',
            'country' => 'CZ',
            'client_name' => 'Client ' . uniqid(),
            'client_street' => 'C St',
            'client_city' => 'C City',
            'client_zip' => '54321',
            'client_country' => 'CZ',
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

    #[Test]
    public function user_can_generate_pdf(): void
    {
        // Create one product line so items flow into DTO/renderer
        $products = [new InvoiceProductWriteData(
            name: 'Service A',
            quantity: '2',
            unit: Unit::PIECES,
            price: Money::fromString('50', 'EUR'),
            taxRate: '21',
        )];
        $create = $this->mutationService->create(
            $this->user->id,
            $this->baseData(),
            $products,
        );
        $this->assertInvoiceSuccess($create, 'Invoice creation failed');
        $pdf = $this->pdfService->generateForUser($this->user->id, $create->invoice->id);
        $this->assertInvoiceSuccess($pdf, 'PDF generation failed');
        $this->assertNotNull($pdf->response);
        $this->assertEquals(InvoiceActionStatus::SUCCESS, $pdf->status);
    }

    #[Test]
    public function guest_can_preview_pdf_by_token(): void
    {
        // Simulate guest temporary invoice storage
        $data = $this->baseData();
        $data['lang'] = 'en';
        // Use the same store as the application service for temporary invoices
        $store = app(TemporaryInvoiceStoreInterface::class);
        $token = $store->store($data);
        $this->assertNotNull($store->get($token), 'Temporary store did not retain data');
        $result = $this->pdfService->generateForGuestByToken(
            $token,
            'en',
            true,
        ); // preview stream
        $this->assertInvoiceSuccess($result, 'Guest preview failed');
        $this->assertNotNull($result->response);
    }

    #[Test]
    public function another_users_invoice_is_not_rendered(): void
    {
        $create = $this->mutationService->create(
            $this->user->id,
            $this->baseData(),
            [],
        );
        $this->assertInvoiceSuccess($create, 'Invoice creation failed');
        $otherUser = User::factory()->create();

        $pdf = $this->pdfService->generateForUser($otherUser->id, $create->invoice->id);

        $this->assertInvoiceFailure($pdf);
        $this->assertSame('invoices.messages.show_error', $pdf->message);
        $this->assertNull($pdf->response);
    }

    #[Test]
    public function guest_without_a_token_gets_not_found(): void
    {
        $result = $this->pdfService->generateForGuestByToken(
            null,
            'cs',
            false,
        );

        $this->assertInvoiceFailure($result);
        $this->assertSame('invoices.messages.not_found', $result->message);
    }

    #[Test]
    public function guest_with_an_unknown_token_gets_expired(): void
    {
        $result = $this->pdfService->generateForGuestByToken(
            'unknown-token',
            'cs',
            false,
        );

        $this->assertInvoiceFailure($result);
        $this->assertSame('invoices.messages.expired', $result->message);
    }
}
