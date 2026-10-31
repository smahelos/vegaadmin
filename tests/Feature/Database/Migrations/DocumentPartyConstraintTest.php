<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AssertsDatabaseRefusal;
use Tests\TestCase;

/**
 * A document refers only to the business, contact and account of its own user, and its account belongs to the
 * party that is paid: the business on an invoice, the supplier contact on an expense.
 */
final class DocumentPartyConstraintTest extends TestCase
{
    use AssertsDatabaseRefusal;

    /**
     * @return iterable<string, array{class-string<Invoice|Expense>}>
     */
    public static function documents(): iterable
    {
        yield 'invoice' => [Invoice::class];
        yield 'expense' => [Expense::class];
    }

    /**
     * @param  class-string<Invoice|Expense>  $model
     */
    #[Test]
    #[DataProvider('documents')]
    public function a_document_refers_only_to_a_business_and_contact_of_its_user(string $model): void
    {
        $document = $model::factory()->create();
        $ownContact = Contact::factory()->create(['user_id' => $document->user_id]);

        $document->forceFill(['contact_id' => $ownContact->id])->save();

        $this->assertChangeRefused(
            $document,
            ['business_id' => Business::factory()->create()->id],
            1452,
        );
        $this->assertChangeRefused(
            $document,
            ['contact_id' => Contact::factory()->create()->id],
            1452,
        );
        $this->assertDatabaseHas($document->getTable(), ['id' => $document->id, 'contact_id' => $ownContact->id]);
    }

    #[Test]
    public function an_invoice_account_belongs_to_the_invoice_business(): void
    {
        $invoice = Invoice::factory()->create();
        $business = Business::findOrFail($invoice->business_id);
        $ownAccount = BankAccount::factory()->ownedBy($business)->create();
        $otherBusiness = Business::factory()->create(['user_id' => $invoice->user_id]);

        $invoice->forceFill(['bank_account_id' => $ownAccount->id])->save();

        $this->assertChangeRefused(
            $invoice,
            ['bank_account_id' => BankAccount::factory()->ownedBy($otherBusiness)->create()->id],
            1452,
        );
        $this->assertChangeRefused(
            $invoice,
            ['bank_account_id' => BankAccount::factory()->ownedBy(Contact::factory()->create(['user_id' => $invoice->user_id]))->create()->id],
            1452,
        );
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'bank_account_id' => $ownAccount->id]);
    }

    #[Test]
    public function an_expense_account_belongs_to_the_expense_contact(): void
    {
        $expense = Expense::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $expense->user_id]);
        $ownAccount = BankAccount::factory()->ownedBy($contact)->create();
        $otherContact = Contact::factory()->create(['user_id' => $expense->user_id]);

        $expense->forceFill(['contact_id' => $contact->id, 'bank_account_id' => $ownAccount->id])->save();

        $this->assertChangeRefused(
            $expense,
            ['bank_account_id' => BankAccount::factory()->ownedBy($otherContact)->create()->id],
            1452,
        );
        $this->assertChangeRefused(
            $expense,
            ['bank_account_id' => BankAccount::factory()->ownedBy(Business::findOrFail($expense->business_id))->create()->id],
            1452,
        );
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'bank_account_id' => $ownAccount->id]);
    }

    /**
     * MySQL skips a composite foreign key with a NULL column, so the CHECK keeps an account from a one-off supplier.
     */
    #[Test]
    public function an_expense_without_a_contact_has_no_account_reference(): void
    {
        $expense = Expense::factory()->create();
        $account = BankAccount::factory()->ownedBy(Contact::factory()->create(['user_id' => $expense->user_id]))->create();

        $this->assertChangeRefused(
            $expense,
            ['bank_account_id' => $account->id],
            3819,
        );
    }

    /**
     * @param  array<string, int>  $attributes
     */
    private function assertChangeRefused(
        Model $document,
        array $attributes,
        int $mysqlErrorCode,
    ): void {
        $this->assertRefused(fn () => $document->forceFill($attributes)->save(), $mysqlErrorCode);
        $document->refresh();
    }
}
