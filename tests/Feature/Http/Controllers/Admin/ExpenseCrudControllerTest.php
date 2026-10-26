<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Models\EntityLimit;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Supplier;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ExpenseCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    private UniversalLimitService $limitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->limitService = app(UniversalLimitService::class);
        // Ensure regular user has specific expense permission if exists
        $permission = Permission::where('name', 'can_create_edit_expense')
            ->where('guard_name', 'backpack')
            ->first();
        if ($permission) {
            $this->regularUser->givePermissionTo($permission);
        }
    }

    private function ensureStatus(): Status
    {
        return Status::factory()->create([
            'category_id' => StatusCategory::where('slug', 'expense-statuses')->firstOrFail()->id,
        ]);
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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = $this->ensureStatus();
        $category = ExpenseCategory::factory()->create();

        $initial = $this->limitService->checkLimit(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
        );
        $initialUsage = $initial['current_usage'] ?? 0;

        $expense = Expense::factory()->create([
            'user_id' => $this->regularUser->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'status_id' => $status->id,
            'category_id' => $category->id,
        ]);

        $result = $this->limitService->recordUsage(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
            'backpack',
        );
        $this->assertTrue($result, 'recordUsage should return true when successful');

        $after = $this->limitService->checkLimit(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
        );
        $afterUsage = $after['current_usage'] ?? 0;

        $this->assertEquals($initialUsage + 1, $afterUsage);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'user_id' => $this->regularUser->id]);
    }

    #[Test]
    public function edit_form_lists_the_attachments_with_their_file_type_icon(): void
    {
        $expense = Expense::factory()->create([
            'user_id' => $this->regularUser->id,
            'supplier_id' => Supplier::factory()->create(['user_id' => $this->regularUser->id])->id,
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail()->id,
            'status_id' => $this->ensureStatus()->id,
            'category_id' => ExpenseCategory::factory()->create()->id,
            'attachments' => ['expenses/receipt.pdf'],
        ]);

        $response = $this->actingAs($this->regularUser, 'backpack')
            ->get(backpack_url('expense/' . $expense->id . '/edit'));

        $response->assertOk();
        $response->assertSee('receipt.pdf');
        $response->assertSee('la-file-pdf');
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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
        $status = $this->ensureStatus();
        $category = ExpenseCategory::factory()->create();

        $firstCheck = $this->limitService->checkLimit(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
        );
        $this->assertTrue($firstCheck['allowed']);

        $expense1 = Expense::factory()->create([
            'user_id' => $this->regularUser->id,
            'supplier_id' => $supplier->id,
            'payment_method_id' => $paymentMethod->id,
            'status_id' => $status->id,
            'category_id' => $category->id,
        ]);
        $result1 = $this->limitService->recordUsage(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
            'backpack',
        );
        $this->assertTrue($result1, 'First recordUsage should succeed');

        $secondCheck = $this->limitService->checkLimit(
            $this->regularUser->id,
            'expense',
            'count',
            'monthly',
        );
        $this->assertFalse($secondCheck['allowed']);
        $this->assertEquals('limit_exceeded', $secondCheck['reason']);

        $this->assertDatabaseHas('expenses', ['id' => $expense1->id, 'user_id' => $this->regularUser->id]);
    }
}
