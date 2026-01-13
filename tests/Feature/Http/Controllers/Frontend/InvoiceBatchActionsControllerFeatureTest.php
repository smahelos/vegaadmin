<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceBatchActionsControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        \Spatie\Permission\Models\Permission::firstOrCreate(['name'=>'frontend.can_create_edit_invoice','guard_name'=>'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
        // Fake permissive limits using only generic methods (specialized removed in refactor)
        $fakeLimit = new class implements \App\Domain\User\Contracts\UniversalLimitServiceInterface {
            public function checkLimit(?int $userId,string $entityType,string $metricType='count',string $periodType='daily',int|float $value=1): array {
                return ['allowed'=>true,'limit'=>PHP_INT_MAX,'current_usage'=>0,'period_type'=>$periodType];
            }
            public function recordUsage(?int $userId,string $entityType,string $metricType='count',?string $periodType='daily',string $guard='web',int|float $value=1): bool { return true; }
            public function resetUsage(?int $userId,string $entityType,string $metricType='count',string $periodType='daily'): bool { return true; }
            public function getUsageStatistics(?int $userId,string $entityType,string $metricType='count',string $periodType='daily'): array { return ['current_usage'=>0,'limit'=>PHP_INT_MAX,'allowed'=>true]; }
            public function getBestPeriodType(?int $userId,string $entityType,string $metricType='count'): string { return 'daily'; }
            public function getEntityLimitInfo(?int $userId,string $entityType): array { return []; }
            public function canUserCreateEntity(?int $userId, string $entityType): bool { return true; }
            // Temporary compatibility wrappers (will disappear after interface refactor)
        };
        $this->app->instance(\App\Domain\User\Contracts\UniversalLimitServiceInterface::class,$fakeLimit);
    }

    private function baseData(): array
    {
        \App\Models\Status::firstOrCreate(['slug'=>'paid'], [
            'name'=>'Paid',
            'category_id'=>\App\Models\StatusCategory::factory()->create()->id,
            'color'=>'bg-green-100 text-green-800',
            'description'=>'Paid status','is_active'=>true
        ]);
        return [
            'invoice_vs' => 'INV-'.uniqid(),
            'payment_method_id' => \App\Models\PaymentMethod::factory()->create()->id,
            'payment_amount' => 20,
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
    public function batch_actions_template_and_mark_paid_success(): void
    {
        $this->actingAs($this->user);
        $invoice = app(\App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface::class)
            ->create($this->user->id, $this->baseData(), [])->invoice;
        $response = $this->post(route('frontend.invoice.batch-actions', ['locale'=>app()->getLocale(),'id'=>$invoice->id]), [
            'template' => 'modern',
            'mark_paid' => true,
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function batch_actions_rejects_invalid_template(): void
    {
        $this->actingAs($this->user);
        $invoice = app(\App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface::class)
            ->create($this->user->id, $this->baseData(), [])->invoice;
        $response = $this->post(route('frontend.invoice.batch-actions', ['locale'=>app()->getLocale(),'id'=>$invoice->id]), [
            'template' => 'invalid-template',
        ]);
        $response->assertSessionHasErrors('template');
    }
}
