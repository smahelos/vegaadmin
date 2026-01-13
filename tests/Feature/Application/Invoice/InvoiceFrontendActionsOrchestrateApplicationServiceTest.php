<?php

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoiceFrontendActionsOrchestrateApplicationServiceTest extends TestCase
{
    use RefreshDatabase;
    use AssertsInvoiceActionResult;

    private InvoiceFrontendActionsApplicationServiceInterface $frontend;
    private InvoiceMutationApplicationServiceInterface $mutation;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        \Spatie\Permission\Models\Permission::firstOrCreate(['name'=>'frontend.can_create_edit_invoice','guard_name'=>'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
        $fakeLimit = new class implements \App\Domain\User\Contracts\UniversalLimitServiceInterface {
            public function checkLimit(?int $userId,string $entityType,string $metricType='count',string $periodType='daily',int|float $value=1): array {return ['allowed'=>true,'current_usage'=>0,'limit'=>50,'remaining'=>50,'entity_type'=>$entityType,'metric_type'=>$metricType,'period_type'=>$periodType,'reason'=>'within_limit'];}
            public function recordUsage(?int $userId,string $entityType,string $metricType='count',?string $periodType='daily',string $guard='web',int|float $value=1): bool {return true;}
            public function resetUsage(?int $userId,string $entityType,string $metricType='count',string $periodType='daily'): bool {return true;}
            public function getUsageStatistics(?int $userId,string $entityType,string $metricType='count',string $periodType='daily'): array {return ['current_usage'=>0,'limit'=>50,'allowed'=>true];}
            public function getBestPeriodType(?int $userId,string $entityType,string $metricType='count'): string {return 'daily';}
            public function getEntityLimitInfo(?int $userId,string $entityType): array {return [];}
            public function canUserCreateEntity(?int $userId,string $entityType): bool {return true;}
        };
        $this->app->instance(\App\Domain\User\Contracts\UniversalLimitServiceInterface::class,$fakeLimit);
        $this->frontend = app(InvoiceFrontendActionsApplicationServiceInterface::class);
        $this->mutation = app(InvoiceMutationApplicationServiceInterface::class);
    }

    private function baseData(): array
    {
        // Ensure paid status exists for potential mark paid result message
        \App\Models\Status::firstOrCreate(['slug'=>'paid'], [
            'name'=>'Paid',
            'category_id'=>\App\Models\StatusCategory::factory()->create()->id,
            'color'=>'bg-green-100 text-green-800',
            'description'=>'Paid status','is_active'=>true
        ]);
        return [
            'invoice_vs' => 'INV-'.uniqid(),
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
            'payment_amount' => 10,
            'payment_currency' => 'EUR',
            'issue_date' => now()->format('Y-m-d'),
            'due_in' => 14,
            'payment_status_id' => \App\Models\Status::factory()->create()->id,
            'name' => 'Supp '.uniqid(),
            'street' => 'Street',
            'city' => 'City',
            'zip' => '10000',
            'country' => 'CZ',
            'client_name' => 'Client '.uniqid(),
            'client_street' => 'C St',
            'client_city' => 'C City',
            'client_zip' => '99999',
            'client_country' => 'CZ',
            'invoice_text' => 'Txt',
        ];
    }

    #[Test]
    public function can_orchestrate_template_and_mark_paid(): void
    {
        $create = $this->mutation->create($this->user->id, $this->baseData(), []);
        $this->assertInvoiceSuccess($create, 'Create failed');
        $invoiceId = $create->invoice->id;
        $result = $this->frontend->orchestrate(
            $this->user->id,
            $invoiceId,
            \App\Application\Invoice\DTO\OrchestratedInvoiceActions::fromArray([
                'template' => 'modern',
                'mark_paid' => true,
            ], includeInvoice: true)
        );
        $this->assertInvoiceSuccess($result, 'Orchestrate failed');
        $this->assertIsArray($result->data);
        $this->assertNotNull($result->invoice, 'Expected invoice to be attached when includeInvoice=true');
    }

    #[Test]
    public function orchestrate_partial_fail_when_status_invalid_but_template_ok(): void
    {
        $create = $this->mutation->create($this->user->id, $this->baseData(), []);
        $invoiceId = $create->invoice->id;
        $result = $this->frontend->orchestrate(
            $this->user->id,
            $invoiceId,
            \App\Application\Invoice\DTO\OrchestratedInvoiceActions::fromArray([
                'template' => 'modern',
                'status' => 'nonexistent-status-slug',
            ], includeInvoice: true)
        );
        $this->assertInvoiceFailure($result, 'Expected failure due to invalid status');
        $this->assertIsArray($result->data);
        $this->assertArrayHasKey('errors', $result->data);
        $this->assertArrayHasKey('status', $result->data['errors']);
        $this->assertNull($result->invoice, 'Invoice should not be attached on failure');
    }
}
