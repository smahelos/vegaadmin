<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Invoice\Contracts\ExpenseWriteRepositoryInterface;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\EntityLimit;
use App\Models\Expense;
use App\Models\FileHash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsExpenseInput;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class ExpenseCrudControllerTest extends TestCase
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

    /** @return array<string, array{array<string, mixed>}> */
    public static function emptyAttachments(): array
    {
        return [
            'no field' => [[]],
            'empty list' => [['attachments' => []]],
            'no selection in a browser' => [['attachments' => ['']]],
            'null field' => [['attachments' => null]],
        ];
    }

    #[Test]
    #[DataProvider('emptyAttachments')]
    public function creates_and_updates_an_expense_without_an_attachment(array $attachments): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business, $attachments);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors()->assertRedirect();
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $this->assertSame([], $expense->attachments);
        $this->put('/admin/expense/' . $expense->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([], $expense->fresh()->attachments);
    }

    #[Test]
    public function create_and_edit_forms_send_attachments_as_multipart_data(): void
    {
        $this->get('/admin/expense/create')->assertOk()->assertSee('enctype="multipart/form-data"', false);
        $expense = Expense::factory()->create();
        $this->get('/admin/expense/' . $expense->id . '/edit')->assertOk()->assertSee('enctype="multipart/form-data"', false);
    }

    #[Test]
    public function creates_an_expense_with_the_business_as_customer_and_a_one_off_supplier(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business, [
            'account_number' => '2001234561',
            'account_bank_code' => '2010',
            'account_country' => 'CZ',
        ]);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'reference_number' => $data['reference_number'],
            'business_id' => $business->id,
            'customer_name' => $business->name,
            'supplier_name' => 'One-off supplier',
            'amount' => '123.45',
            'account_number' => '2001234561',
        ]);
    }

    #[Test]
    public function an_expenses_own_business_needs_no_address_and_keeps_its_optional_customer_fields(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        Address::where('business_id', $business->id)->delete();
        $data = $this->expenseInput($business);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'reference_number' => $data['reference_number'],
            'business_id' => $business->id,
            'customer_name' => $business->name,
            'customer_street' => null,
            'customer_city' => null,
            'supplier_name' => 'One-off supplier',
        ]);
    }

    #[Test]
    public function a_new_contact_and_its_account_are_copied_and_typed_values_are_ignored(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id]);
        $address = Address::factory()->create(['business_id' => null, 'contact_id' => $contact->id]);
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $data = $this->expenseInput($business, [
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
            'supplier_country' => 'invalid',
            'account_iban' => 'invalid',
        ]);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'reference_number' => $data['reference_number'],
            'supplier_name' => $contact->name,
            'supplier_street' => $address->street,
            'account_number' => $account->number,
        ]);
    }

    #[Test]
    public function keeping_the_contact_and_account_preserves_the_account_snapshot_and_saves_document_corrections(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id, 'archived_at' => now()]);
        $account = BankAccount::factory()->ownedBy($contact)->create(['archived_at' => now()]);
        $expense = Expense::factory()->create([
            'user_id' => $this->adminUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
            'account_number' => '2001234561',
            'account_bank_code' => '2010',
            'account_country' => 'CZ',
        ]);
        $this->put('/admin/expense/' . $expense->id, $this->expenseInput($business, [
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
            'supplier_name' => 'Document correction',
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Document correction', $expense->fresh()->supplier_name);
        $this->assertSame('2001234561', $expense->fresh()->account_number);
        $this->assertSame($contact->name, $contact->fresh()->name);
    }

    #[Test]
    public function a_changed_contact_replaces_the_supplier(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $expense = Expense::factory()->create(['user_id' => $this->adminUser->id, 'business_id' => $business->id]);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id]);
        Address::factory()->create(['business_id' => null, 'contact_id' => $contact->id]);
        $this->put('/admin/expense/' . $expense->id, $this->expenseInput($business, ['contact_id' => $contact->id]))->assertSessionHasNoErrors();
        $this->assertSame($contact->name, $expense->fresh()->supplier_name);
    }

    #[Test]
    public function an_account_of_a_different_contact_is_rejected(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id]);
        $account = BankAccount::factory()->ownedBy(Contact::factory()->create(['user_id' => $this->adminUser->id]))->create();
        $this->post('/admin/expense', $this->expenseInput($business, ['contact_id' => $contact->id, 'bank_account_id' => $account->id]))->assertSessionHasErrors('bank_account_id');
    }

    #[Test]
    public function another_owners_business_is_rejected(): void
    {
        [$business] = $this->issuerOf($this->regularUser);
        $this->post('/admin/expense', $this->expenseInput($business, ['user_id' => $this->adminUser->id]))->assertSessionHasErrors('business_id');
    }

    #[Test]
    public function newly_selected_archived_contacts_are_rejected(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id, 'archived_at' => now()]);
        $this->post('/admin/expense', $this->expenseInput($business, ['contact_id' => $contact->id]))->assertSessionHasErrors('contact_id');
    }

    #[Test]
    public function editing_with_the_wrong_owner_returns_not_found(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $expense = Expense::factory()->create(['user_id' => $this->regularUser->id]);
        $this->put('/admin/expense/' . $expense->id, $this->expenseInput($business))->assertNotFound();
    }

    #[Test]
    public function the_limit_belongs_to_the_owner_and_creation_records_usage_once(): void
    {
        [$business] = $this->issuerOf($this->regularUser);
        $this->regularUser->givePermissionTo('can_create_expense');
        EntityLimit::factory()->create([
            'entity_type' => 'expense',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 1,
            'permission_name' => 'can_create_expense',
            'is_active' => true,
        ]);
        $data = $this->expenseInput($business);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('entity_limit_usage', [
            'user_id' => $this->regularUser->id,
            'entity_type' => 'expense',
            'current_value' => 1,
        ]);
        $this->post('/admin/expense', array_replace($data, ['reference_number' => 'blocked']))->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['reference_number' => 'blocked']);
    }

    #[Test]
    public function a_web_plan_allowance_is_consumed_when_an_admin_creates_an_expense(): void
    {
        $this->regularUser->assignRole(Role::findByName('frontend_user', 'web'));
        $this->regularUser->givePermissionTo(Permission::findByName('frontend.can_create_edit_invoice', 'web'));
        EntityLimit::factory()->create([
            'entity_type' => 'expense',
            'metric_type' => 'count',
            'period_type' => 'monthly',
            'limit_value' => 1,
            'permission_name' => 'frontend.can_create_edit_invoice',
            'is_active' => true,
        ]);
        [$business] = $this->issuerOf($this->regularUser);
        $data = $this->expenseInput($business);
        $this->post('/admin/expense', $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('entity_limit_usage', [
            'user_id' => $this->regularUser->id,
            'entity_type' => 'expense',
            'current_value' => 1,
        ]);
        $this->post('/admin/expense', array_replace($data, ['reference_number' => 'WEB-BLOCKED']))->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['reference_number' => 'WEB-BLOCKED']);
    }

    #[Test]
    public function a_failed_expense_keeps_a_deduplicated_attachment_and_removes_only_new_files(): void
    {
        Storage::fake('public');
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business);
        $receipt = '%PDF-1.4 shared receipt';
        $this->post('/admin/expense', $data + [
            'attachments' => [UploadedFile::fake()->createWithContent('receipt.pdf', $receipt)],
        ])->assertSessionHasNoErrors();
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $path = $expense->attachments[0];
        $this->assertDatabaseHas('file_hashes', ['path' => $path, 'ref_count' => 1]);
        $this->mock(ExpenseWriteRepositoryInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('Persistence failed'));
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/admin/expense', array_replace($data, [
                'reference_number' => 'FAILED',
                'attachments' => [
                    UploadedFile::fake()->createWithContent('receipt.pdf', $receipt),
                    UploadedFile::fake()->createWithContent('new.pdf', '%PDF-1.4 new receipt'),
                ],
            ]));
            $this->fail('The expense write did not fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Persistence failed', $exception->getMessage());
        }
        Storage::disk('public')->assertExists($path);
        $this->assertSame([$path], Storage::disk('public')->allFiles('expenses/attachments'));
        $this->assertSame([$path], $expense->fresh()->attachments);
        $this->assertDatabaseHas('file_hashes', ['path' => $path, 'ref_count' => 1]);
        $this->assertSame(1, FileHash::count());
        $this->assertDatabaseMissing('expenses', ['reference_number' => 'FAILED']);
    }

    #[Test]
    public function uploads_preserves_and_removes_only_the_expenses_own_attachments(): void
    {
        Storage::fake('public');
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business);
        $this->post('/admin/expense', $data + ['attachments' => [UploadedFile::fake()->create('receipt.pdf', 12, 'application/pdf')]])->assertSessionHasNoErrors();
        $expense = Expense::where('reference_number', $data['reference_number'])->sole();
        $path = $expense->attachments[0];
        Storage::disk('public')->assertExists($path);
        $this->get('/admin/expense/' . $expense->id . '/edit')->assertOk()->assertSee(basename($path))->assertSee('la-file-pdf');
        $this->put('/admin/expense/' . $expense->id, $data)->assertSessionHasNoErrors();
        $this->assertSame([$path], $expense->fresh()->attachments);
        Storage::disk('public')->put('someone-else.pdf', 'private');
        $this->put('/admin/expense/' . $expense->id, $data + ['attachments_removed' => json_encode([$path, 'someone-else.pdf'])])->assertSessionHasNoErrors();
        $this->assertSame([], $expense->fresh()->attachments);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists('someone-else.pdf');
    }

    #[Test]
    public function show_and_list_render_the_saved_supplier(): void
    {
        $expense = Expense::factory()->create(['supplier_name' => 'Saved supplier']);
        $this->get('/admin/expense/' . $expense->id . '/show')->assertOk()->assertSee('Saved supplier');
        $this->get('/admin/expense')->assertOk();
    }

    #[Test]
    public function guests_and_users_without_permission_cannot_write_expenses(): void
    {
        auth('backpack')->logout();
        $this->post('/admin/expense', [])->assertRedirect(route('backpack.auth.login'));
        $this->actingAs($this->regularUser, 'backpack')->post('/admin/expense', [])->assertForbidden();
        $this->get('/admin/expense/create')->assertForbidden();
    }
}
