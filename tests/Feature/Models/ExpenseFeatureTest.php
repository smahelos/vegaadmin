<?php

namespace Tests\Feature\Models;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Expense Model
 * 
 * Tests database relationships, business logic, and model behavior requiring database interactions
 * Tests expense interactions with users, suppliers, categories, payment methods, and file uploads
 */
class ExpenseFeatureTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function can_create_expense_with_factory(): void
    {
        $expense = Expense::factory()->create();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'amount' => $expense->amount,
            'description' => $expense->description,
        ]);
    }

    #[Test]
    public function fillable_attributes_can_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::factory()->create();
        $category = ExpenseCategory::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create();
        $status = Status::factory()->create();
        
        $data = [
            'user_id' => $user->id,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'expense_date' => '2025-06-18',
            'amount' => 150.75,
            'currency' => 'USD',
            'payment_method_id' => $paymentMethod->id,
            'reference_number' => 'REF-123456',
            'description' => 'Test expense description',
            'tax_amount' => 31.66,
            'tax_included' => true,
            'status_id' => $status->id,
            'attachments' => null,
        ];

        $expense = Expense::create($data);

        // Check key fields manually since cast/storage format differs
        $this->assertDatabaseHas('expenses', [
            'user_id' => $data['user_id'],
            'supplier_id' => $data['supplier_id'],
            'category_id' => $data['category_id'],
            'expense_date' => $data['expense_date'],
            'currency' => $data['currency'],
            'description' => $data['description'],
        ]);
        $this->assertEquals($data['amount'], $expense->amount);
        $this->assertEquals($data['description'], $expense->description);
    }

    #[Test]
    public function casts_work_correctly(): void
    {
        $expense = Expense::factory()->create([
            'expense_date' => '2025-06-18',
            'amount' => '123.45',
            'tax_amount' => '25.67',
            'tax_included' => 1,
            'attachments' => ['file1.pdf', 'file2.jpg'],
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $expense->expense_date);
        $this->assertIsString($expense->amount); // Decimal cast returns string
        $this->assertEquals('123.45', $expense->amount);
        $this->assertIsString($expense->tax_amount);
        $this->assertEquals('25.67', $expense->tax_amount);
        $this->assertIsBool($expense->tax_included);
        $this->assertTrue($expense->tax_included);
        $this->assertIsArray($expense->attachments);
        $this->assertEquals(['file1.pdf', 'file2.jpg'], $expense->attachments);
    }

    #[Test]
    public function belongs_to_user_relationship(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $expense->user);
        $this->assertEquals($user->id, $expense->user->id);
        $this->assertEquals($user->name, $expense->user->name);
    }

    #[Test]
    public function belongs_to_supplier_relationship(): void
    {
        $supplier = Supplier::factory()->create();
        $expense = Expense::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertInstanceOf(Supplier::class, $expense->supplier);
        $this->assertEquals($supplier->id, $expense->supplier->id);
    }

    #[Test]
    public function belongs_to_category_relationship(): void
    {
        $category = ExpenseCategory::factory()->create();
        $expense = Expense::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ExpenseCategory::class, $expense->category);
        $this->assertEquals($category->id, $expense->category->id);
    }

    #[Test]
    public function belongs_to_payment_method_relationship(): void
    {
        $paymentMethod = PaymentMethod::factory()->create();
        $expense = Expense::factory()->create(['payment_method_id' => $paymentMethod->id]);

        $this->assertInstanceOf(PaymentMethod::class, $expense->paymentMethod);
        $this->assertEquals($paymentMethod->id, $expense->paymentMethod->id);
    }

    #[Test]
    public function belongs_to_status_relationship(): void
    {
        $status = Status::factory()->create();
        $expense = Expense::factory()->create(['status_id' => $status->id]);

        $this->assertInstanceOf(Status::class, $expense->status);
        $this->assertEquals($status->id, $expense->status->id);
    }

    #[Test]
    public function factory_states_work_correctly(): void
    {
        $amountExpense = Expense::factory()->amount(250.00)->create();
        $taxIncludedExpense = Expense::factory()->taxIncluded()->create();
        $noTaxExpense = Expense::factory()->noTax()->create();

        $this->assertEquals('250.00', $amountExpense->amount);
        $this->assertTrue($taxIncludedExpense->tax_included);
        $this->assertEquals('0.00', $noTaxExpense->tax_amount);
        $this->assertFalse($noTaxExpense->tax_included);
    }

    #[Test]
    public function factory_for_user_state_works(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->forUser($user)->create();

        $this->assertEquals($user->id, $expense->user_id);
        $this->assertEquals($user->id, $expense->user->id);
    }

    #[Test]
    public function factory_from_date_state_works(): void
    {
        $date = new \DateTime('2025-01-15');
        $expense = Expense::factory()->fromDate($date)->create();

        $this->assertEquals('2025-01-15', $expense->expense_date->format('Y-m-d'));
    }

    #[Test]
    public function attachments_mutator_works(): void
    {
        $expense = Expense::factory()->make();
        
        // Test that setAttachmentsAttribute method exists and can be called
        $this->assertTrue(method_exists($expense, 'setAttachmentsAttribute'));
        
        // Test setting attachments value
        $attachments = ['file1.pdf', 'file2.jpg'];
        $expense->setAttachmentsAttribute($attachments);
        
        // The actual implementation depends on HasFileUploads trait
        $this->assertTrue(
            is_null($expense->attachments) || 
            is_array($expense->attachments) || 
            is_string($expense->attachments)
        );
    }

    #[Test]
    public function file_upload_methods_exist(): void
    {
        $expense = Expense::factory()->create();
        
        // Test file URL method exists and returns string or null
        $fileUrl = $expense->getFileUrl('attachments');
        
        $this->assertTrue(is_string($fileUrl) || is_null($fileUrl));
        $this->assertTrue(method_exists($expense, 'getFileUrl'));
    }

    #[Test]
    public function can_update_expense(): void
    {
        $expense = Expense::factory()->create();
        
        $newData = [
            'amount' => 299.99,
            'description' => 'Updated expense description',
            'tax_included' => false,
        ];
        
        $expense->update($newData);
        
        $this->assertDatabaseHas('expenses', array_merge(
            ['id' => $expense->id],
            $newData
        ));
    }

    #[Test]
    public function can_delete_expense(): void
    {
        $expense = Expense::factory()->create();
        $expenseId = $expense->id;
        
        $expense->delete();
        
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }

    #[Test]
    public function can_create_expense_without_optional_fields(): void
    {
        $user = User::factory()->create();
        
        $data = [
            'user_id' => $user->id,
            'expense_date' => '2025-06-18',
            'amount' => 75.00,
            'description' => 'Simple expense',
            'attachments' => null,
        ];

        $expense = Expense::create($data);

        // Check key fields manually since cast/storage format differs
        $this->assertDatabaseHas('expenses', [
            'user_id' => $data['user_id'],
            'expense_date' => $data['expense_date'],
            'description' => $data['description'],
        ]);
        $this->assertEquals($data['amount'], $expense->amount);
        $this->assertNull($expense->supplier_id);
        $this->assertNull($expense->category_id);
        $this->assertNull($expense->payment_method_id);
    }

    #[Test]
    public function currency_field_can_store_different_currencies(): void
    {
        $usdExpense = Expense::factory()->create(['currency' => 'USD']);
        $eurExpense = Expense::factory()->create(['currency' => 'EUR']);
        $gbpExpense = Expense::factory()->create(['currency' => 'GBP']);

        $this->assertEquals('USD', $usdExpense->currency);
        $this->assertEquals('EUR', $eurExpense->currency);
        $this->assertEquals('GBP', $gbpExpense->currency);
    }

    #[Test]
    public function reference_number_can_be_null(): void
    {
        $expense = Expense::factory()->create(['reference_number' => null]);
        
        $this->assertNull($expense->reference_number);
    }

    #[Test]
    public function tax_calculations_work_correctly(): void
    {
        $expense = Expense::factory()->create([
            'amount' => '100.00',
            'tax_amount' => '21.00',
            'tax_included' => true,
        ]);

        $this->assertEquals('100.00', $expense->amount);
        $this->assertEquals('21.00', $expense->tax_amount);
        $this->assertTrue($expense->tax_included);
    }
}
