<?php

namespace Tests\Feature\Http\Requests;

use App\Http\Requests\ExpenseRequest;
use App\Models\User;
use App\Models\Supplier;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature tests for ExpenseRequest
 * 
 * Tests complete validation flow with HTTP context and database interactions
 * Tests expense validation scenarios, authorization, and validation with database constraints
 */
class ExpenseRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $targetUser;
    protected Supplier $supplier;
    protected ExpenseCategory $category;
    protected PaymentMethod $paymentMethod;
    protected Status $status;
    protected array $validExpenseData;

    /**
     * Set up the test environment.
     * Creates test user and valid expense data for request testing.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions and user
        $this->createPermissionsAndUser();
        
        // Create related models
        $this->createRelatedModels();
        
        // Set up valid expense data
        $this->setupValidExpenseData();
    }

    /**
     * Create necessary permissions and test user
     */
    private function createPermissionsAndUser(): void
    {
        // Create permissions
        Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'web']);
        
        // Create role
        $userRole = Role::firstOrCreate(['name' => 'expense_manager', 'guard_name' => 'web']);
        $userRole->givePermissionTo('can_create_edit_expense');
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ]);
        $this->user->assignRole($userRole);

        // Create target user for expenses
        $this->targetUser = User::factory()->create();
    }

    /**
     * Create related models for testing
     */
    private function createRelatedModels(): void
    {
        $this->supplier = Supplier::factory()->create();
        $this->category = ExpenseCategory::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();
        $this->status = Status::factory()->create();
    }

    /**
     * Setup valid expense data for testing
     */
    private function setupValidExpenseData(): void
    {
        $this->validExpenseData = [
            'expense_date' => $this->faker->date(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'CZK',
            'supplier_id' => $this->supplier->id,
            'category_id' => $this->category->id,
            'payment_method_id' => $this->paymentMethod->id,
            'reference_number' => $this->faker->numerify('REF-####'),
            'description' => $this->faker->sentence,
            'tax_amount' => $this->faker->randomFloat(2, 0, 50),
            'status_id' => $this->status->id,
            'user_id' => $this->targetUser->id,
        ];
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $this->actingAs($this->user);
        
        $request = new ExpenseRequest();
        $validator = Validator::make($this->validExpenseData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $this->actingAs($this->user);
        
        $requiredFields = ['expense_date', 'amount', 'currency', 'category_id', 'user_id'];
        
        foreach ($requiredFields as $field) {
            $invalidData = $this->validExpenseData;
            unset($invalidData[$field]);

            $request = new ExpenseRequest();
            $validator = Validator::make($invalidData, $request->rules());

            $this->assertTrue($validator->fails(), "Validation should fail when {$field} is missing");
            $this->assertArrayHasKey($field, $validator->errors()->toArray(), "Should have error for missing {$field}");
        }
    }

    #[Test]
    public function validation_fails_with_invalid_date()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['expense_date'] = 'invalid-date';

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('expense_date', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_negative_amount()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['amount'] = -100;

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_currency_format()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['currency'] = 'INVALID'; // Must be exactly 3 characters

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_supplier_id()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['supplier_id'] = 99999;

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_category_id()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['category_id'] = 99999;

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_user_id()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['user_id'] = 99999;

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $this->actingAs($this->user);
        
        $minimalData = [
            'expense_date' => $this->faker->date(),
            'amount' => 100.50,
            'currency' => 'EUR',
            'category_id' => $this->category->id,
            'user_id' => $this->targetUser->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($minimalData, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $this->actingAs($this->user);
        
        $dataWithNulls = $this->validExpenseData;
        $dataWithNulls['supplier_id'] = null;
        $dataWithNulls['payment_method_id'] = null;
        $dataWithNulls['reference_number'] = null;
        $dataWithNulls['description'] = null;
        $dataWithNulls['tax_amount'] = null;
        $dataWithNulls['status_id'] = null;

        $request = new ExpenseRequest();
        $validator = Validator::make($dataWithNulls, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_negative_tax_amount()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['tax_amount'] = -10;

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tax_amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_too_long_reference_number()
    {
        $this->actingAs($this->user);
        
        $invalidData = $this->validExpenseData;
        $invalidData['reference_number'] = str_repeat('a', 256); // Too long (max 255)

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('reference_number', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_file_upload()
    {
        $this->actingAs($this->user);
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('receipt.pdf', 1024); // 1MB file
        
        $dataWithFile = $this->validExpenseData;
        $dataWithFile['receipt_file'] = $file;

        $request = new ExpenseRequest();
        $validator = Validator::make($dataWithFile, $request->rules());

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    #[Test]
    public function validation_fails_with_too_large_file()
    {
        $this->actingAs($this->user);
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('large_receipt.pdf', 11000); // 11MB file (exceeds 10MB limit)
        
        $dataWithFile = $this->validExpenseData;
        $dataWithFile['receipt_file'] = $file;

        $request = new ExpenseRequest();
        $validator = Validator::make($dataWithFile, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('receipt_file', $validator->errors()->toArray());
    }

    #[Test]
    public function authorization_fails_without_permission()
    {
        // Create user without permission
        $userWithoutPermission = User::factory()->create();
        $this->actingAs($userWithoutPermission);

        $request = new ExpenseRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorization_fails_when_not_authenticated()
    {
        $request = new ExpenseRequest();
        
        $this->assertFalse($request->authorize());
    }

    #[Test]
    public function authorization_passes_with_correct_permission()
    {
        $this->actingAs($this->user);

        $request = new ExpenseRequest();
        
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ExpenseRequest();
        $attributes = $request->attributes();

        $expectedKeys = [
            'expense_date', 'amount', 'currency', 'supplier_id', 'category_id',
            'payment_method_id', 'reference_number', 'description', 'receipt_file',
            'tax_amount', 'status_id'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $attributes, "Should have custom attribute for {$key}");
        }
    }

    #[Test]
    public function validation_passes_with_different_valid_currencies()
    {
        $this->actingAs($this->user);
        
        $validCurrencies = ['CZK', 'EUR', 'USD', 'GBP'];
        
        foreach ($validCurrencies as $currency) {
            $validData = $this->validExpenseData;
            $validData['currency'] = $currency;

            $request = new ExpenseRequest();
            $validator = Validator::make($validData, $request->rules());

            $this->assertFalse($validator->fails(), "Validation should pass for currency: {$currency}");
        }
    }
}
