<?php

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseRequestFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permission for expense management
        Permission::firstOrCreate(['name' => 'can_create_edit_expense', 'guard_name' => 'backpack']);
    }

    #[Test]
    public function validation_passes_with_valid_data()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $supplier = Supplier::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();

        $validData = [
            'expense_date' => '2024-01-15',
            'amount' => 1500.50,
            'currency' => 'CZK',
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'payment_method_id' => $paymentMethod->id,
            'reference_number' => 'REF-2024-001',
            'description' => 'Test expense description',
            'tax_amount' => 315.10,
            'status_id' => $status->id,
            'user_id' => $user->id,
            'tax_included' => true,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_when_required_fields_missing()
    {
        $invalidData = [];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('expense_date', $validator->errors()->toArray());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_date()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => 'invalid-date',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('expense_date', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_negative_amount()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => -100,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_invalid_currency_format()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZKKK', // Too long
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_supplier_id()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'supplier_id' => 99999, // Non-existent
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('supplier_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_category_id()
    {
        $user = User::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => 99999, // Non-existent
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('category_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_nonexistent_user_id()
    {
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => 99999, // Non-existent
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('user_id', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_minimal_required_data()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $validData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_passes_with_nullable_fields_set_to_null()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $validData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'supplier_id' => null,
            'payment_method_id' => null,
            'reference_number' => null,
            'description' => null,
            'tax_amount' => null,
            'status_id' => null,
            'tax_included' => null,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_negative_tax_amount()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'tax_amount' => -50,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('tax_amount', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_fails_with_too_long_reference_number()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'reference_number' => str_repeat('a', 256),
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('reference_number', $validator->errors()->toArray());
    }

    #[Test]
    public function validation_passes_with_valid_file_upload()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $file = UploadedFile::fake()->create('receipt.pdf', 100); // 100KB

        $validData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'receipt_file' => $file,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($validData, $request->rules());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function validation_fails_with_too_large_file()
    {
        Storage::fake('local');
        
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $file = UploadedFile::fake()->create('receipt.pdf', 1024001); // Over 10MB

        $invalidData = [
            'expense_date' => '2024-01-15',
            'amount' => 1000,
            'currency' => 'CZK',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'receipt_file' => $file,
        ];

        $request = new ExpenseRequest();
        $validator = Validator::make($invalidData, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('receipt_file', $validator->errors()->toArray());
    }

    #[Test]
    public function authorization_fails_without_permission()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'backpack');

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
        $user = User::factory()->create();
        $permission = Permission::where('name', 'can_create_edit_expense')->first();
        $user->givePermissionTo($permission);
        
        $this->actingAs($user, 'backpack');

        $request = new ExpenseRequest();
        $this->assertTrue($request->authorize());
    }

    #[Test]
    public function request_has_custom_attributes()
    {
        $request = new ExpenseRequest();
        $attributes = $request->attributes();

        $this->assertIsArray($attributes);
    }

    #[Test]
    public function validation_passes_with_different_valid_currencies()
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::factory()->create();

        $currencies = ['CZK', 'EUR', 'USD', 'GBP'];

        foreach ($currencies as $currency) {
            $validData = [
                'expense_date' => '2024-01-15',
                'amount' => 1000,
                'currency' => $currency,
                'category_id' => $category->id,
                'user_id' => $user->id,
            ];

            $request = new ExpenseRequest();
            $validator = Validator::make($validData, $request->rules());

            $this->assertTrue($validator->passes(), "Currency {$currency} should be valid");
        }
    }
}
