<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Domain\Invoice\DTO\ExpenseDTO;
use App\Domain\Invoice\DTO\ExpenseWriteData;
use App\Domain\Invoice\ValueObjects\ExpenseId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\User\ValueObjects\UserId;
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
        $invoice = Invoice::where('invoice_vs', $data['invoice_vs'])->sole();
        $copy = $invoice->supplier_logo;
        $this->assertOwnCopy($business->logo, $copy);
        $httpResponse2 = $this->get('/admin/invoice/' . $invoice->id . '/show');
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2->assertSee($copy);
        Storage::disk('public')->delete($business->logo);
        $business->update(
            ['logo' => $this->sourceLogo('new.png')],
        );
        $httpResponse3 = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse3 = $httpResponse3->assertSessionHasNoErrors();
        $httpResponse3->assertRedirect();
        $this->assertSame($copy, $invoice->fresh()->supplier_logo);
        Storage::disk('public')->assertExists($copy);
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
        $invoice = Invoice::where('invoice_vs', $data['invoice_vs'])->sole();
        $oldCopy = $invoice->supplier_logo;
        [$second, $secondAccount] = $this->issuerOf($this->adminUser);
        $data['business_id'] = $second->id;
        $data['bank_account_id'] = $secondAccount->id;
        $httpResponse2 = $this->put('/admin/invoice/' . $invoice->id, $data);
        $httpResponse2->assertSessionHasNoErrors();
        $this->assertNull($invoice->fresh()->supplier_logo);
        Storage::disk('public')->assertMissing($oldCopy);
        Storage::disk('public')->assertExists($business->logo);
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
        $this->assertSame(
            [],
            Storage::disk('public')->allFiles('documents/logos'),
        );
        Storage::disk('public')->assertExists($business->logo);
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
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $copy = $expense->supplier_logo;
        $this->assertOwnCopy($contact->logo, $copy);
        $httpResponse2 = $this->get('/admin/expense/' . $expense->id . '/show');
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2->assertSee($copy);
        $this->assertNotSame(
            Storage::disk('public')->get($business->logo),
            Storage::disk('public')->get($copy),
        );
        Storage::disk('public')->delete($contact->logo);
        $contact->update(['logo' => null]);
        [$secondBusiness] = $this->issuerOf($this->adminUser);
        $data['business_id'] = $secondBusiness->id;
        // Editing the saved form submits its copied supplier identity.
        $data['supplier_company_number'] = $expense->supplier_company_number;
        $data['supplier_registration_country'] = $expense->supplier_registration_country;
        $httpResponse3 = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse3->assertSessionHasNoErrors();
        $this->assertSame($copy, $expense->fresh()->supplier_logo);
        $this->assertSame($contact->company_number, $expense->fresh()->supplier_company_number);
        Storage::disk('public')->assertExists($copy);
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
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $old = $expense->supplier_logo;
        $data['contact_id'] = $second->id;
        $httpResponse2 = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse2->assertSessionHasNoErrors();
        $copy = $expense->fresh()->supplier_logo;
        $this->assertOwnCopy($second->logo, $copy);
        Storage::disk('public')->assertMissing($old);
        $data['contact_id'] = null;
        $httpResponse3 = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse3->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->supplier_logo);
        Storage::disk('public')->assertMissing($copy);
    }

    #[Test]
    public function a_one_off_expense_logo_belongs_only_to_that_document(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $business->update(
            ['logo' => $this->sourceLogo('own.png')],
        );
        $data = $this->expenseInput(
            $business,
            ['supplier_logo' => UploadedFile::fake()->image('external.png')],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertSessionHasNoErrors();
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $copy = $expense->supplier_logo;
        $this->assertNotNull($copy);
        $this->assertNotSame($business->logo, $copy);
        unset($data['supplier_logo']);
        $httpResponse2 = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse2->assertSessionHasNoErrors();
        $this->assertSame($copy, $expense->fresh()->supplier_logo);
        $data['remove_supplier_logo'] = '1';
        $httpResponse3 = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse3->assertSessionHasNoErrors();
        $this->assertNull($expense->fresh()->supplier_logo);
        Storage::disk('public')->assertMissing($copy);
        $this->assertSame($business->logo, $business->fresh()->logo);
    }

    #[Test]
    public function rejects_an_invalid_one_off_logo(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput(
                $business,
                [
                    'supplier_logo' => UploadedFile::fake()->create(
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
        $httpResponse2 = $this->post(
            '/admin/contact',
            [
                'user_id' => $owner->id,
                'name' => $name,
                'logo' => UploadedFile::fake()->image('logo.png'),
            ],
        );
        $httpResponse2 = $httpResponse2->assertSessionHasNoErrors();
        $httpResponse2->assertRedirect();
        $contact = Contact::where('name', $name)->sole();
        $this->assertNotNull($contact->logo);
        Storage::disk('public')->assertExists($contact->logo);
        $httpResponse3 = $this->get('/admin/contact/' . $contact->id . '/edit');
        $httpResponse3 = $httpResponse3->assertOk();
        $httpResponse3->assertSee('enctype="multipart/form-data"', false);
        $first = $contact->logo;
        $httpResponse4 = $this->put('/admin/contact/' . $contact->id, ['name' => $name]);
        $httpResponse4->assertSessionHasNoErrors();
        $this->assertSame($first, $contact->fresh()->logo);
        $httpResponse5 = $this->put(
            '/admin/contact/' . $contact->id,
            [
                'name' => $name,
                'logo' => UploadedFile::fake()->image(
                    'replacement.png',
                    300,
                    120,
                ),
            ],
        );
        $httpResponse5->assertSessionHasNoErrors();
        $second = $contact->fresh()->logo;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
        $httpResponse6 = $this->put('/admin/contact/' . $contact->id, ['name' => $name, 'remove_logo' => '1']);
        $httpResponse6->assertSessionHasNoErrors();
        $this->assertNull($contact->fresh()->logo);
        Storage::disk('public')->assertMissing($second);
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
        $httpResponse2 = $this->post('/admin/invoice', $secondInput);
        $httpResponse2->assertSessionHasNoErrors();
        $first = Invoice::where('invoice_vs', $firstInput['invoice_vs'])->sole();
        $second = Invoice::where('invoice_vs', $secondInput['invoice_vs'])->sole();
        $this->assertNotSame($first->supplier_logo, $second->supplier_logo);
        [$withoutLogo, $otherAccount] = $this->issuerOf($this->adminUser);
        $firstInput['business_id'] = $withoutLogo->id;
        $firstInput['bank_account_id'] = $otherAccount->id;
        $httpResponse3 = $this->put('/admin/invoice/' . $first->id, $firstInput);
        $httpResponse3->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($first->supplier_logo);
        Storage::disk('public')->assertExists($second->supplier_logo);
        Storage::disk('public')->assertExists($business->logo);
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
        $invoice = Invoice::where('invoice_vs', $input['invoice_vs'])->sole();
        [$nextBusiness, $nextAccount] = $this->issuerOf($this->adminUser);
        $nextBusiness->update(
            ['logo' => $this->sourceLogo('after.png')],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $input['business_id'] = $nextBusiness->id;
        $input['bank_account_id'] = $nextAccount->id;
        $input['contact_id'] = $contact->id;
        $httpResponse2 = $this->put('/admin/invoice/' . $invoice->id, $input);
        $httpResponse2->assertSessionHasErrors('contact_id');
        $this->assertSame($invoice->supplier_logo, $invoice->fresh()->supplier_logo);
        $this->assertSame(
            [$invoice->supplier_logo],
            Storage::disk('public')->allFiles('documents/logos'),
        );
        Storage::disk('public')->assertExists($nextBusiness->logo);
    }

    #[Test]
    public function a_failed_expense_save_discards_both_the_logo_and_attachments(): void
    {
        $this->app->instance(
            ExpenseWriteRepositoryInterface::class,
            new class implements ExpenseWriteRepositoryInterface
            {
                public function create(
                    UserId $userId,
                    ExpenseWriteData $data,
                    DocumentParties $parties,
                    array $attachments,
                ): ExpenseDTO {
                    throw new RuntimeException('Expense persistence failed');
                }

                public function update(
                    ExpenseId $id,
                    UserId $userId,
                    ExpenseWriteData $data,
                    DocumentParties $parties,
                    array $attachments,
                ): ExpenseDTO {
                    throw new RuntimeException('Expense persistence failed');
                }
            },
        );
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput(
            $business,
            [
                'supplier_logo' => UploadedFile::fake()->image('supplier.png'),
                'attachments' => [UploadedFile::fake()->create(
                    'receipt.pdf',
                    12,
                    'application/pdf',
                )],
            ],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertServerError();
        $this->assertSame(
            [],
            Storage::disk('public')->allFiles(),
        );
        $this->assertDatabaseMissing('expenses', ['reference_number' => $data['reference_number']]);
    }

    private function sourceLogo(string $name): string
    {
        $path = 'sources/' . $name;
        Storage::disk('public')->put($path, $name);

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
        $this->assertSame(
            Storage::disk('public')->get($source),
            Storage::disk('public')->get($copy),
        );
    }
}
