<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for Expense Model
 *
 * Tests database relationships, business logic, and model behavior requiring database interactions
 * Tests expense persistence and related records
 */
final class ExpenseFeatureTest extends TestCase
{
    use WithFaker;

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
    public function stores_the_document_snapshot_with_the_expense(): void
    {
        $expense = Expense::factory()->create([
            'supplier_name' => 'Document supplier',
            'customer_name' => 'Document customer',
            'attachments' => ['receipts/receipt.pdf'],
        ])->fresh();

        $this->assertSame('Document supplier', $expense->supplier_name);
        $this->assertSame('Document customer', $expense->customer_name);
        $this->assertSame(['receipts/receipt.pdf'], $expense->attachments);
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

        $this->assertInstanceOf(Carbon::class, $expense->expense_date);
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
    public function the_expense_links_its_business_contact_and_contact_account(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $expense = Expense::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
        ]);

        $this->assertSame($user->id, $expense->business->user_id);
        $this->assertSame($contact->id, $expense->contact->id);
        $this->assertSame($account->id, $expense->bankAccount->id);
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
        $paymentMethod = PaymentMethod::where('slug', PaymentMethodCode::BANK_ACCOUNT->value)->firstOrFail();
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
            $newData,
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
    public function can_store_an_expense_without_a_contact_or_account(): void
    {
        $expense = Expense::factory()->create(['contact_id' => null, 'bank_account_id' => null]);

        $this->assertNull($expense->fresh()->contact);
        $this->assertNull($expense->fresh()->bankAccount);
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
