<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ExpenseCrudControllerTest extends TestCase
{
    use RefreshDatabase;
    use CreatesAdminTestEnvironment;

    private User $adminUser;
    private User $regularUser;
    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->limitService = app(UniversalLimitService::class);
        // Ensure regular user has specific expense permission if exists
        $permission = \Spatie\Permission\Models\Permission::where('name', 'can_create_edit_expense')
            ->where('guard_name', 'backpack')
            ->first();
        if ($permission) {
            $this->regularUser->givePermissionTo($permission);
        }
    }

    private function ensureStatus(): Status
    {
        $category = StatusCategory::factory()->create(['slug' => 'expense-statuses']);
        return Status::factory()->create(['category_id' => $category->id]);
    }

    #[Test]
    public function expense_creation_records_entity_usage(): void
    {
        $this->actingAs($this->regularUser, 'backpack');

        EntityLimit::factory()->create([
            'entity_type' => 'expense',
            'limit_value' => 10,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_expense',
            'is_active' => true,
        ]);

        $supplier = Supplier::factory()->create(['user_id' => $this->regularUser->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = $this->ensureStatus();
        $category = ExpenseCategory::factory()->create();

        $initial = $this->limitService->checkLimit($this->regularUser->id, 'expense', 'count', 'monthly');
        $initialUsage = $initial['current_usage'] ?? 0;

        $expense = Expense::factory()->create([
            'user_id' => $this->regularUser->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'status_id' => $status->id,
            'category_id' => $category->id,
        ]);

        $result = $this->limitService->recordUsage($this->regularUser->id, 'expense', 'count', 'monthly', 'backpack');
        $this->assertTrue($result, 'recordUsage should return true when successful');

        $after = $this->limitService->checkLimit($this->regularUser->id, 'expense', 'count', 'monthly');
        $afterUsage = $after['current_usage'] ?? 0;

        $this->assertEquals($initialUsage + 1, $afterUsage);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'user_id' => $this->regularUser->id]);
    }

    #[Test]
    public function expense_creation_respects_entity_limits(): void
    {
        $this->actingAs($this->regularUser, 'backpack');

        EntityLimit::factory()->create([
            'entity_type' => 'expense',
            'limit_value' => 1,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'permission_name' => 'can_create_edit_expense',
            'is_active' => true,
        ]);

        $supplier = Supplier::factory()->create(['user_id' => $this->regularUser->id]);
        $paymentMethod = PaymentMethod::factory()->create();
        $status = $this->ensureStatus();
        $category = ExpenseCategory::factory()->create();

        $firstCheck = $this->limitService->checkLimit($this->regularUser->id, 'expense', 'count', 'monthly');
        $this->assertTrue($firstCheck['allowed']);

        $expense1 = Expense::factory()->create([
            'user_id' => $this->regularUser->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'status_id' => $status->id,
            'category_id' => $category->id,
        ]);
        $result1 = $this->limitService->recordUsage($this->regularUser->id, 'expense', 'count', 'monthly', 'backpack');
        $this->assertTrue($result1, 'First recordUsage should succeed');

        $secondCheck = $this->limitService->checkLimit($this->regularUser->id, 'expense', 'count', 'monthly');
        $this->assertFalse($secondCheck['allowed']);
        $this->assertEquals('limit_exceeded', $secondCheck['reason']);

    $this->assertDatabaseHas('expenses', ['id' => $expense1->id, 'user_id' => $this->regularUser->id]);
    }
}
