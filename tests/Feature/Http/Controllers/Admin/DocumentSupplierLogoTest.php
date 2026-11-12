<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsExpenseInput;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class DocumentSupplierLogoTest extends TestCase
{
    use BuildsExpenseInput;
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
        Storage::fake('public');
    }

    #[Test]
    public function an_invoice_copies_its_business_logo_and_keeps_the_file_after_the_source_is_replaced(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('business.png')],
        );
        $data = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->adminUser->id, 'payment_amount' => '100'],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $invoiceQuery = Invoice::where('invoice_vs', $data['invoice_vs']);
        $invoice = $invoiceQuery->sole();
        $copy = $invoice->supplier_logo;
        $this->assertOwnCopy($business->logo, $copy);
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/show');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee($copy);
        $disk = Storage::disk('public');
        $disk->delete($business->logo);
        $business->update(
            ['logo' => $this->sourceLogo('new.png')],
        );
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        $disk->assertExists($copy);
    }

    #[Test]
    public function changing_the_business_replaces_the_invoice_copy_and_clears_it_when_the_business_has_no_logo(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('first.png')],
        );
        $data = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->adminUser->id, 'payment_amount' => '100'],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse->assertSessionHasNoErrors();
        $invoiceQuery = Invoice::where('invoice_vs', $data['invoice_vs']);
        $invoice = $invoiceQuery->sole();
        $oldCopy = $invoice->supplier_logo;
        [$second, $secondAccount] = $this->issuerOf($this->adminUser);
        $data['business_id'] = $second->id;
        $data['bank_account_id'] = $secondAccount->id;
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertNull($invoice->fresh()->supplier_logo);
        $disk = Storage::disk('public');
        $disk->assertMissing($oldCopy);
        $disk->assertExists($business->logo);
    }

    #[Test]
    public function a_failed_invoice_save_removes_its_new_logo_copy(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('business.png')],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $data = $this->invoiceInput(
            $business,
            $account,
            [
                'user_id' => $this->adminUser->id,
                'payment_amount' => '100',
                'contact_id' => $contact->id,
            ],
        );
        $httpResponse = $this->post('/admin/invoice', $data);
        $httpResponse->assertSessionHasErrors('contact_id');
        $disk = Storage::disk('public');
        $this->assertSame(
            [],
            $disk->allFiles('documents/logos'),
        );
        $disk->assertExists($business->logo);
        $this->assertDatabaseMissing('invoices', ['invoice_vs' => $data['invoice_vs']]);
    }

    #[Test]
    public function an_expense_uses_the_external_contact_logo_and_keeps_it_when_its_business_changes(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('own-company.png')],
        );
        $contact = $this->supplierContact('external.png');
        $data = $this->expenseInput($business, ['contact_id' => $contact->id]);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertSessionHasNoErrors();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $copy = $expense->supplier_logo;
        $this->assertOwnCopy($contact->logo, $copy);
        $httpResponse = $this->get('/admin/expense/' . $expense->id . '/show');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee($copy);
        $disk = Storage::disk('public');
        $this->assertNotSame(
            $disk->get($business->logo),
            $disk->get($copy),
        );
        $disk->delete($contact->logo);
        $contact->update(['logo' => null]);
        [$secondBusiness] = $this->issuerOf($this->adminUser);
        $data['business_id'] = $secondBusiness->id;
        // Editing the saved form submits its copied supplier identity.
        $data['supplier_company_number'] = $expense->supplier_company_number;
        $data['supplier_registration_country'] = $expense->supplier_registration_country;
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $freshExpense = $expense->fresh();
        $this->assertSame($copy, $freshExpense->supplier_logo);
        $this->assertSame($contact->company_number, $freshExpense->supplier_company_number);
        $disk->assertExists($copy);
    }

    #[Test]
    public function changing_an_expense_contact_copies_the_new_logo_and_removing_the_contact_clears_it(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $first = $this->supplierContact('first.png');
        $second = $this->supplierContact('second.png');
        $data = $this->expenseInput($business, ['contact_id' => $first->id]);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertSessionHasNoErrors();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $old = $expense->supplier_logo;
        $data['contact_id'] = $second->id;
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $copy = $expense->fresh()->supplier_logo;
        $this->assertOwnCopy($second->logo, $copy);
        $disk = Storage::disk('public');
        $disk->assertMissing($old);
        $data['contact_id'] = null;
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->supplier_logo);
        $disk->assertMissing($copy);
    }

    #[Test]
    public function a_one_off_expense_logo_belongs_only_to_that_document(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('own.png')],
        );
        $fileFactory = UploadedFile::fake();
        $data = $this->expenseInput(
            $business,
            ['supplier_logo' => $fileFactory->image('external.png')],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertSessionHasNoErrors();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $copy = $expense->supplier_logo;
        $this->assertNotNull($copy);
        $this->assertNotSame($business->logo, $copy);
        unset($data['supplier_logo']);
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertSame($copy, $expense->fresh()->supplier_logo);
        $data['remove_supplier_logo'] = '1';
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->supplier_logo);
        $disk = Storage::disk('public');
        $disk->assertMissing($copy);
        $this->assertSame($business->logo, $business->fresh()->logo);
    }

    #[Test]
    public function rejects_an_invalid_one_off_logo(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $fileFactory = UploadedFile::fake();
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput(
                $business,
                [
                    'supplier_logo' => $fileFactory->create(
                        'document.pdf',
                        10,
                        'application/pdf',
                    ),
                ],
            ),
        );
        $httpResponse->assertSessionHasErrors('supplier_logo');
    }

    #[Test]
    public function contact_upload_is_multipart_and_stores_a_source_logo(): void
    {
        $httpResponse = $this->get('/admin/contact/create');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('enctype="multipart/form-data"', false);
        $userFactory = User::factory();
        $owner = $userFactory->create();
        $owner->assignRole(
            Role::findByName('frontend_user', 'web'),
        );
        $name = 'Contact with logo ' . uniqid();
        $fileFactory = UploadedFile::fake();
        $httpResponse = $this->post(
            '/admin/contact',
            [
                'user_id' => $owner->id,
                'name' => $name,
                'logo' => $fileFactory->image('logo.png'),
            ],
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $contactQuery = Contact::where('name', $name);
        $contact = $contactQuery->sole();
        $this->assertNotNull($contact->logo);
        $disk = Storage::disk('public');
        $disk->assertExists($contact->logo);
        $httpResponse = $this->get('/admin/contact/' . $contact->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('enctype="multipart/form-data"', false);
        $first = $contact->logo;
        $httpResponse = $this->put('/admin/contact/' . $contact->id, ['name' => $name]);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertSame($first, $contact->fresh()->logo);
        $httpResponse = $this->put(
            '/admin/contact/' . $contact->id,
            [
                'name' => $name,
                'logo' => $fileFactory->image(
                    'replacement.png',
                    300,
                    120,
                ),
            ],
        );
        $httpResponse->assertSessionHasNoErrors();
        $second = $contact->fresh()->logo;
        $this->assertNotSame($first, $second);
        $disk->assertMissing($first);
        $disk->assertExists($second);
        $httpResponse = $this->put('/admin/contact/' . $contact->id, ['name' => $name, 'remove_logo' => '1']);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertNull($contact->fresh()->logo);
        $disk->assertMissing($second);
    }

    #[Test]
    public function documents_with_the_same_source_have_independent_files(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('shared.png')],
        );
        $firstInput = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->adminUser->id, 'payment_amount' => '100'],
        );
        $secondInput = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->adminUser->id, 'payment_amount' => '100'],
        );
        $httpResponse = $this->post('/admin/invoice', $firstInput);
        $httpResponse->assertSessionHasNoErrors();
        $httpResponse = $this->post('/admin/invoice', $secondInput);
        $httpResponse->assertSessionHasNoErrors();
        $invoiceQuery = Invoice::where('invoice_vs', $firstInput['invoice_vs']);
        $first = $invoiceQuery->sole();
        $invoiceQuery = Invoice::where('invoice_vs', $secondInput['invoice_vs']);
        $second = $invoiceQuery->sole();
        $this->assertNotSame($first->supplier_logo, $second->supplier_logo);
        [$withoutLogo, $otherAccount] = $this->issuerOf($this->adminUser);
        $firstInput['business_id'] = $withoutLogo->id;
        $firstInput['bank_account_id'] = $otherAccount->id;
        $httpResponse = $this->put('/admin/invoice/' . $first->id, $firstInput);
        $httpResponse->assertSessionHasNoErrors();
        $disk = Storage::disk('public');
        $disk->assertMissing($first->supplier_logo);
        $disk->assertExists($second->supplier_logo);
        $disk->assertExists($business->logo);
    }

    #[Test]
    public function a_failed_replacement_keeps_the_saved_logo_and_cleans_only_the_new_copy(): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('before.png')],
        );
        $input = $this->invoiceInput(
            $business,
            $account,
            ['user_id' => $this->adminUser->id, 'payment_amount' => '100'],
        );
        $httpResponse = $this->post('/admin/invoice', $input);
        $httpResponse->assertSessionHasNoErrors();
        $invoiceQuery = Invoice::where('invoice_vs', $input['invoice_vs']);
        $invoice = $invoiceQuery->sole();
        [$nextBusiness, $nextAccount] = $this->issuerOf($this->adminUser);
        $nextBusiness->update(
            ['logo' => $this->sourceLogo('after.png')],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $input['business_id'] = $nextBusiness->id;
        $input['bank_account_id'] = $nextAccount->id;
        $input['contact_id'] = $contact->id;
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, $input);
        $httpResponse->assertSessionHasErrors('contact_id');
        $this->assertSame($invoice->supplier_logo, $invoice->fresh()->supplier_logo);
        $disk = Storage::disk('public');
        $this->assertSame(
            [$invoice->supplier_logo],
            $disk->allFiles('documents/logos'),
        );
        $disk->assertExists($nextBusiness->logo);
    }

    #[Test]
    public function a_failed_expense_save_discards_both_the_logo_and_attachments(): void
    {
        $this->app->instance(
            ExpenseWriteRepositoryInterface::class,
            new class implements ExpenseWriteRepositoryInterface
            {
                public function create(
                    WriteScope $scope,
                    ExpenseWriteData $data,
                    DocumentParties $parties,
                    array $attachments,
                ): ExpenseDTO {
                    throw new RuntimeException('Expense persistence failed');
                }

                public function update(
                    ExpenseId $id,
                    WriteScope $scope,
                    ExpenseWriteData $data,
                    DocumentParties $parties,
                    array $attachments,
                ): ExpenseDTO {
                    throw new RuntimeException('Expense persistence failed');
                }
            },
        );
        [$business] = $this->issuerOf($this->adminUser);
        $fileFactory = UploadedFile::fake();
        $data = $this->expenseInput(
            $business,
            [
                'supplier_logo' => $fileFactory->image('supplier.png'),
                'attachments' => [$fileFactory->create(
                    'receipt.pdf',
                    12,
                    'application/pdf',
                )],
            ],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertServerError();
        $disk = Storage::disk('public');
        $this->assertSame(
            [],
            $disk->allFiles(),
        );
        $this->assertDatabaseMissing('expenses', ['reference_number' => $data['reference_number']]);
    }

    private function sourceLogo(string $name): string
    {
        $path = 'sources/' . $name;
        $disk = Storage::disk('public');
        $disk->put($path, $name);

        return $path;
    }

    private function supplierContact(string $logo): Contact
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            ['user_id' => $this->adminUser->id, 'logo' => $this->sourceLogo($logo)],
        );
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => null, 'contact_id' => $contact->id]);

        return $contact;
    }

    private function assertOwnCopy(string $source, ?string $copy): void
    {
        $this->assertNotNull($copy);
        $this->assertNotSame($source, $copy);
        $this->assertStringStartsWith('documents/logos/', $copy);
        $disk = Storage::disk('public');
        $this->assertSame(
            $disk->get($source),
            $disk->get($copy),
        );
    }
}
