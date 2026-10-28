<?php

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Application\Invoice\DTO\OrchestratedInvoiceActions;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoiceFrontendActionsOrchestrateApplicationServiceTest extends TestCase
{
    use AssertsInvoiceActionResult;

    private InvoiceFrontendActionsApplicationServiceInterface $frontend;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_invoice', 'guard_name' => 'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
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
                string $periodType = 'daily',
            ): bool {
                return true;
            }

            public function getUsageStatistics(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                string $periodType = 'daily',
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
        $this->frontend = app(InvoiceFrontendActionsApplicationServiceInterface::class);
    }

    private function pendingInvoiceId(): int
    {
        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PENDING->value)->firstOrFail()->id,
        ])->id;
    }

    #[Test]
    public function can_orchestrate_template_and_mark_paid(): void
    {
        $invoiceId = $this->pendingInvoiceId();
        $result = $this->frontend->orchestrate(
            $this->user->id,
            $invoiceId,
            OrchestratedInvoiceActions::fromArray([
                'template' => 'modern',
                'mark_paid' => true,
            ], includeInvoice: true),
        );
        $this->assertInvoiceSuccess($result, 'Orchestrate failed');
        $this->assertIsArray($result->data);
        $this->assertNotNull($result->invoice, 'Expected invoice to be attached when includeInvoice=true');
    }
}
