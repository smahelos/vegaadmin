<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin;

use App\Models\Expense;
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
        $this->post('/admin/expense', $this->expenseInput($business, [$field => $value]))->assertSessionHasErrors($field === 'attachments' ? 'attachments.0' : $field);
    }

    #[Test]
    public function an_expense_can_be_created_without_an_account(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expenses', [
            'reference_number' => $data['reference_number'],
            'bank_account_id' => null,
            'account_number' => null,
        ]);
    }

    #[Test]
    public function updating_requires_the_complete_document(): void
    {
        $expense = Expense::factory()->create(['user_id' => $this->adminUser->id]);
        $this->put('/admin/expense/' . $expense->id, ['description' => 'Changed'])->assertSessionHasErrors([
            'business_id',
            'user_id',
            'supplier_name',
        ]);
    }
}
