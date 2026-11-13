<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Http\Requests\Admin\ExpenseRequest;
use App\Models\Contact;
use App\Models\Expense;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsExpenseInput;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class ExpenseRequestFeatureTest extends TestCase
{
    use BuildsExpenseInput;
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidInput(): array
    {
        return [
            'missing business' => ['business_id', null],
            'missing owner' => ['user_id', null],
            'missing name' => ['supplier_name', ''],
            'missing street' => ['supplier_street', ''],
            'missing city' => ['supplier_city', ''],
            'missing postal code' => ['supplier_postal_code', ''],
            'invalid country' => ['supplier_country', 'invalid'],
            'negative amount' => ['amount', '-1'],
            'excess decimals' => ['amount', '1.234'],
            'invalid currency' => ['currency', 'ABC'],
            'invalid category' => ['category_id', 99999999],
            'invalid date' => ['expense_date', 'invalid'],
            'fake attachment path' => ['attachments', ['someone-else.pdf']],
            'invalid account' => ['account_iban', 'invalid'],
        ];
    }

    #[Test]
    #[DataProvider('invalidInput')]
    public function rejects_invalid_document_input(string $field, mixed $value): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput($business, [$field => $value]),
        );
        $httpResponse->assertSessionHasErrors($field === 'attachments' ? 'attachments.0' : $field);
    }

    #[Test]
    public function an_expense_with_a_contact_keeps_an_uploaded_supplier_logo(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $fileFactory = UploadedFile::fake();
        $request = ExpenseRequest::create(
            '/admin/expense',
            'POST',
            $this->expenseInput($business, ['contact_id' => $contact->id]),
            [],
            ['supplier_logo' => $fileFactory->image('logo.png')],
        );
        $request->setContainer($this->app);
        $request->setRedirector(
            app('redirect'),
        );
        $request->validateResolved();

        $writeData = $request->toWriteData();

        $this->assertSame('logo.png', $writeData->supplierLogo?->originalName);
    }

    #[Test]
    public function an_expense_can_be_created_without_an_account(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas(
            'expenses',
            [
                'reference_number' => $data['reference_number'],
                'bank_account_id' => null,
                'account_number' => null,
            ],
        );
    }

    #[Test]
    public function updating_requires_the_complete_document(): void
    {
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(['user_id' => $this->adminUser->id]);
        $httpResponse = $this->put('/admin/expense/' . $expense->id, ['description' => 'Changed']);
        $httpResponse->assertSessionHasErrors([
            'business_id',
            'supplier_name',
        ]);
    }
}
