<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use App\Models\EntityLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

class ExpenseRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreatesAdminTestEnvironment;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        Permission::firstOrCreate([
            'name' => 'can_create_edit_expense',
            'guard_name' => 'backpack',
        ]);
    }

    private function ensureLimit(int $limit = 10): void
    {
        EntityLimit::where('permission_name', 'can_create_edit_expense')->where('entity_type', 'expense')->delete();
        // Create lifetime limit
        EntityLimit::create([
            'permission_name' => 'can_create_edit_expense',
            'entity_type' => 'expense',
            'limit_value' => $limit,
            'period_type' => 'lifetime',
            'metric_type' => 'count',
            'description' => 'Test lifetime limit',
            'is_active' => true,
        ]);
        // Create monthly limit (BaseEntityRequest default period_type)
        EntityLimit::create([
            'permission_name' => 'can_create_edit_expense',
            'entity_type' => 'expense',
            'limit_value' => $limit,
            'period_type' => 'monthly',
            'metric_type' => 'count',
            'description' => 'Test monthly limit',
            'is_active' => true,
        ]);
        $this->artisan('cache:clear');
    }

    private function registerTestRoutes(): void
    {
        Route::post('/testing/admin/expense/create', function (ExpenseRequest $request) {
            $data = $request->validated();
            $data['attachments'] = $data['attachments'] ?? [];
            $expense = Expense::create($data);
            // Record usage with correct guard so subsequent create hits limit
            if (function_exists('backpack_auth') && backpack_auth()->check()) {
                $limitService = app(\App\Domain\User\Contracts\UniversalLimitServiceInterface::class);
                // Use monthly period (default in BaseEntityRequest) and backpack guard
                $limitService->recordUsage(backpack_auth()->user()->id, 'expense', 'count', 'monthly', 'backpack', 1);
            }
            return response()->json($expense, 201);
        });
        Route::patch('/testing/admin/expense/update/{expenseId}', function (ExpenseRequest $request, $expenseId) {
            $data = $request->validated();
            $expense = Expense::findOrFail($expenseId);
            if (!array_key_exists('attachments', $data)) {
                $data['attachments'] = $expense->attachments ?? [];
            }
            // Build update payload only with provided fields (allows partial updates)
            $updatePayload = [];
            foreach ($data as $field => $value) {
                if ($field === 'attachments') {
                    $updatePayload['attachments'] = json_encode($value);
                } else {
                    $updatePayload[$field] = $value;
                }
            }
            if (!empty($updatePayload)) {
                $updatePayload['updated_at'] = now();
                \DB::table('expenses')->where('id', $expense->id)->update($updatePayload);
            }
            return response()->json(Expense::find($expense->id));
        });
    }

    private function baseValidData(array $overrides = []): array
    {
        return array_merge([
            'expense_date' => '2024-01-15',
            'amount' => 1500.50,
            'currency' => 'czk',
            'supplier_id' => Supplier::factory()->create()->id,
            'category_id' => ExpenseCategory::factory()->create()->id,
            'payment_method_id' => PaymentMethod::factory()->create()->id,
            'reference_number' => ' REF-2024-001 ',
            'description' => 'Test expense description',
            'tax_amount' => 315.10,
            'status_id' => Status::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'tax_included' => true,
            'attachments' => [],
        ], $overrides);
    }

    #[Test]
    public function currency_and_reference_are_normalized_on_create(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $this->actingAs($this->adminUser, 'backpack');
        $this->adminUser->givePermissionTo('can_create_edit_expense');

        $response = $this->post('/testing/admin/expense/create', $this->baseValidData());
        $json = $response->json();
        $this->assertIsArray($json);
        $response->assertStatus(201)->assertJsonFragment([
            'currency' => 'CZK',
            'reference_number' => 'REF-2024-001',
        ]);
    }

    #[Test]
    public function currency_and_reference_are_normalized_on_update_with_partial_payload(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $this->actingAs($this->adminUser, 'backpack');
        $this->adminUser->givePermissionTo('can_create_edit_expense');

        $expense = Expense::factory()->create($this->baseValidData());
        $payload = ['currency' => 'eur', 'reference_number' => ' new-ref '];
    $response = $this->patch('/testing/admin/expense/update/' . $expense->id, $payload);
        $response->assertOk();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'currency' => 'EUR',
            'reference_number' => 'new-ref',
        ]);
    }

    #[Test]
    public function entity_limit_blocks_creation_but_allows_update(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit(1);
        $this->actingAs($this->adminUser, 'backpack');
        $this->adminUser->givePermissionTo('can_create_edit_expense');

        $first = $this->postJson('/testing/admin/expense/create', $this->baseValidData());
        $first->assertStatus(201);

        $second = $this->postJson('/testing/admin/expense/create', $this->baseValidData(['reference_number' => 'REF-2']));
        // Limit exceeded returns 403 (EntityLimitExceededException render). Expect JSON 403.
        $second->assertStatus(403);

        $expense = Expense::first();
        $update = $this->patchJson('/testing/admin/expense/update/' . $expense->id, ['description' => 'Updated']);
        $update->assertOk()->assertJsonFragment(['description' => 'Updated']);
    }

    #[Test]
    public function update_allows_partial_fields_due_to_sometimes_rules(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $this->actingAs($this->adminUser, 'backpack');
        $this->adminUser->givePermissionTo('can_create_edit_expense');

        $expense = Expense::factory()->create($this->baseValidData());
    $response = $this->patch('/testing/admin/expense/update/' . $expense->id, [ 'description' => 'Only description changed' ]);
        $response->assertOk();
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Only description changed',
        ]);
    }

    #[Test]
    public function authorization_fails_without_permission(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $this->actingAs($this->regularUser, 'backpack');
        $this->regularUser->revokePermissionTo('can_create_edit_expense');

        $response = $this->post('/testing/admin/expense/create', $this->baseValidData());
        $response->assertStatus(403);
    }

    #[Test]
    public function authorization_fails_when_not_authenticated(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $response = $this->post('/testing/admin/expense/create', $this->baseValidData());
        $response->assertStatus(403);
    }

    #[Test]
    public function authorization_passes_with_permission(): void
    {
        $this->registerTestRoutes();
        $this->ensureLimit();
        $this->actingAs($this->adminUser, 'backpack');
        $this->adminUser->givePermissionTo('can_create_edit_expense');

        $response = $this->post('/testing/admin/expense/create', $this->baseValidData());
        $response->assertStatus(201);
    }
}
