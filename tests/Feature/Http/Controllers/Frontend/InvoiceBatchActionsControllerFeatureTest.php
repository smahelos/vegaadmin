<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Models\Invoice;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InvoiceBatchActionsControllerFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Permission::firstOrCreate(['name' => 'frontend.can_create_edit_invoice', 'guard_name' => 'web']);
        $this->user->givePermissionTo('frontend.can_create_edit_invoice');
        // Fake permissive limits using only generic methods (specialized removed in refactor)
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
                    'limit' => PHP_INT_MAX,
                    'current_usage' => 0,
                    'period_type' => $periodType,
                ];
            }

            public function recordUsage(
                ?int $userId,
                string $entityType,
                string $metricType = 'count',
                ?string $periodType = 'daily',
                ?string $guard = 'web',
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
                    'limit' => PHP_INT_MAX,
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
            // Temporary compatibility wrappers (will disappear after interface refactor)
        };
        $this->app->instance(UniversalLimitServiceInterface::class, $fakeLimit);
    }

    #[Test]
    public function batch_actions_template_and_mark_paid_success(): void
    {
        $this->actingAs($this->user);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $response = $this->post(route('frontend.invoice.batch-actions', ['locale' => app()->getLocale(), 'id' => $invoice->id]), [
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
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $response = $this->post(route('frontend.invoice.batch-actions', ['locale' => app()->getLocale(), 'id' => $invoice->id]), [
            'template' => 'invalid-template',
        ]);
        $response->assertSessionHasErrors('template');
    }

    #[Test]
    public function batch_actions_rejects_unknown_status(): void
    {
        $this->actingAs($this->user);
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $response = $this->post(route('frontend.invoice.batch-actions', ['locale' => app()->getLocale(), 'id' => $invoice->id]), [
            'status' => 'overdue',
        ]);
        $response->assertSessionHasErrors('status');
    }
}
