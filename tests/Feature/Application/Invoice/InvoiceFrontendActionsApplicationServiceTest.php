<?php

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoiceFrontendActionsApplicationServiceTest extends TestCase
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
        // Permissive limit fake registered BEFORE service resolution
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
            // Wrappers for backward compatibility; to be removed after interface cleanup
            // Wrappers slated for removal
            // Removed specialized wrapper methods
        };
        $this->app->instance(UniversalLimitServiceInterface::class, $fakeLimit);
        // Resolve services after binding fake limit
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
    public function can_set_template_and_mark_paid(): void
    {
        $invoiceId = $this->pendingInvoiceId();

        $set = $this->frontend->setTemplate(
            $this->user->id,
            $invoiceId,
            'modern',
        );
        $this->assertInvoiceSuccess($set, 'Set template failed');

        $paid = $this->frontend->markAsPaid($this->user->id, $invoiceId);
        $this->assertInvoiceSuccess($paid, 'Mark paid failed');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'payment_status_id' => Status::where('slug', InvoiceStatus::PAID->value)->value('id'),
        ]);
    }

    #[Test]
    public function can_fetch_limit_data(): void
    {
        $limits = $this->frontend->getLimitData($this->user->id);
        $this->assertInvoiceSuccess($limits, 'Limit data fetch failed');
        $this->assertIsArray($limits->data);
    }
}
