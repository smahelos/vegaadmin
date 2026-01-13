<?php

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoicePdfApplicationServiceTest extends TestCase
{
    use RefreshDatabase;
    use AssertsInvoiceActionResult;

    private InvoicePdfApplicationServiceInterface $pdfService;
    private InvoiceMutationApplicationServiceInterface $mutationService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        \Spatie\Permission\Models\Permission::firstOrCreate(['name'=>'frontend.can_create_edit_invoice','guard_name'=>'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
        // Register fake limit service BEFORE resolving application services
        $fakeLimit = new class implements \App\Domain\User\Contracts\UniversalLimitServiceInterface {
            public function checkLimit(?int $userId,string $entityType,string $metricType='count',string $periodType='daily',int|float $value=1): array {return ['allowed'=>true,'current_usage'=>0,'limit'=>50,'remaining'=>50,'entity_type'=>$entityType,'metric_type'=>$metricType,'period_type'=>$periodType,'reason'=>'within_limit'];}
            public function recordUsage(?int $userId,string $entityType,string $metricType='count',?string $periodType='daily',string $guard='web',int|float $value=1): bool {return true;}
            public function resetUsage(?int $userId,string $entityType,string $metricType='count',?string $periodType='daily'): bool {return true;}
            public function getUsageStatistics(?int $userId,string $entityType,string $metricType='count',?string $periodType='daily'): array {return ['current_usage'=>0,'limit'=>50,'allowed'=>true];}
            public function getBestPeriodType(?int $userId,string $entityType,string $metricType='count'): string {return 'daily';}
            public function getEntityLimitInfo(?int $userId,string $entityType): array {return [];}
            public function canUserCreateEntity(?int $userId,string $entityType): bool {return true;}
        };
        $this->app->instance(\App\Domain\User\Contracts\UniversalLimitServiceInterface::class,$fakeLimit);
        // Now resolve services
        $this->pdfService = app(InvoicePdfApplicationServiceInterface::class);
        $this->mutationService = app(InvoiceMutationApplicationServiceInterface::class);
    }

    private function baseData(): array
    {
        return [
            'invoice_vs' => 'INV-'.uniqid(),
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
            'payment_amount' => 100,
            'payment_currency' => 'EUR',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 14,
            'payment_status_id' => \App\Models\Status::factory()->create()->id,
            'name' => 'Supplier '.uniqid(),
            'street' => 'Main',
            'city' => 'City',
            'zip' => '12345',
            'country' => 'CZ',
            'client_name' => 'Client '.uniqid(),
            'client_street' => 'C St',
            'client_city' => 'C City',
            'client_zip' => '54321',
            'client_country' => 'CZ',
            'invoice_text' => 'Test invoice',
        ];
    }

    #[Test]
    public function user_can_generate_pdf(): void
    {
        // Create one product line so items flow into DTO/renderer
        $products = [
            [ 'name' => 'Service A', 'quantity' => 2, 'price' => 50.0, 'currency' => 'EUR', 'tax_rate' => 21 ]
        ];
        $create = $this->mutationService->create($this->user->id, $this->baseData(), $products);
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
        $store = app(\App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface::class);
        $token = $store->store($data);
        $this->assertNotNull($store->get($token), 'Temporary store did not retain data');
        $result = $this->pdfService->generateForGuestByToken($token, 'en', true); // preview stream
        $this->assertInvoiceSuccess($result, 'Guest preview failed');
        $this->assertNotNull($result->response);
    }
}
