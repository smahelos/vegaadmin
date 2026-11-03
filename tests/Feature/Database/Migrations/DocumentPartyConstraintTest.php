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
        $modelFactory = $model::factory();
        $document = $modelFactory->create();
        $contactFactory = Contact::factory();
        $ownContact = $contactFactory->create(['user_id' => $document->user_id]);

        $document->forceFill(['contact_id' => $ownContact->id]);
        $document->save();

        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $this->assertChangeRefused(
            $document,
            ['business_id' => $business->id],
            1452,
        );
        $contact = $contactFactory->create();
        $this->assertChangeRefused(
            $document,
            ['contact_id' => $contact->id],
            1452,
        );
        $this->assertDatabaseHas($document->getTable(), ['id' => $document->id, 'contact_id' => $ownContact->id]);
    }

    #[Test]
    public function an_invoice_account_belongs_to_the_invoice_business(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();
        $business = Business::findOrFail($invoice->business_id);
        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($business);
        $ownAccount = $ownedFactory->create();
        $businessFactory = Business::factory();
        $otherBusiness = $businessFactory->create(['user_id' => $invoice->user_id]);

        $invoice->forceFill(['bank_account_id' => $ownAccount->id]);
        $invoice->save();

        $ownedFactory = $bankAccountFactory->ownedBy($otherBusiness);
        $otherAccount = $ownedFactory->create();
        $this->assertChangeRefused(
            $invoice,
            ['bank_account_id' => $otherAccount->id],
            1452,
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $invoice->user_id]);
        $ownedFactory = $bankAccountFactory->ownedBy($contact);
        $otherAccount = $ownedFactory->create();
        $this->assertChangeRefused(
            $invoice,
            ['bank_account_id' => $otherAccount->id],
            1452,
        );
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'bank_account_id' => $ownAccount->id]);
    }

    #[Test]
    public function an_expense_account_belongs_to_the_expense_contact(): void
    {
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $expense->user_id]);
        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($contact);
        $ownAccount = $ownedFactory->create();
        $otherContact = $contactFactory->create(['user_id' => $expense->user_id]);

        $expense->forceFill(
            ['contact_id' => $contact->id, 'bank_account_id' => $ownAccount->id],
        );
        $expense->save();

        $ownedFactory = $bankAccountFactory->ownedBy($otherContact);
        $otherAccount = $ownedFactory->create();
        $this->assertChangeRefused(
            $expense,
            ['bank_account_id' => $otherAccount->id],
            1452,
        );
        $business = Business::findOrFail($expense->business_id);
        $ownedFactory = $bankAccountFactory->ownedBy($business);
        $otherAccount = $ownedFactory->create();
        $this->assertChangeRefused(
            $expense,
            ['bank_account_id' => $otherAccount->id],
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
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $contactFactory = Contact::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy(
            $contactFactory->create(['user_id' => $expense->user_id]),
        );
        $account = $bankAccountFactory->create();

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
        $this->assertRefused(
            function () use ($document, $attributes): void {
                $document->forceFill($attributes);
                $document->save();
            },
            $mysqlErrorCode,
        );
        $document->refresh();
    }
}
