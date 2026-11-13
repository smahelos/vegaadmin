<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Expense\Contracts\ExpenseWriteRepositoryInterface;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\FileHash;
use App\Models\RoleLimit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
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
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $this->assertSame([], $expense->attachments);
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertSame([], $expense->fresh()->attachments);
    }

    #[Test]
    public function create_and_edit_forms_send_attachments_as_multipart_data(): void
    {
        $httpResponse = $this->get('/admin/expense/create');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('enctype="multipart/form-data"', false);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create();
        $httpResponse = $this->get('/admin/expense/' . $expense->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('enctype="multipart/form-data"', false);
        $this->assertMatchesRegularExpression(
            '/<select(?=[^>]*name="user_id")(?=[^>]*disabled)[^>]*>/',
            $httpResponse->getContent(),
        );
    }

    #[Test]
    public function creates_an_expense_with_the_business_as_customer_and_a_one_off_supplier(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput(
            $business,
            [
                'account_number' => '2001234561',
                'account_bank_code' => '2010',
                'account_country' => 'CZ',
            ],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'expenses',
            [
                'reference_number' => $data['reference_number'],
                'business_id' => $business->id,
                'customer_name' => $business->name,
                'supplier_name' => 'One-off supplier',
                'amount' => '123.45',
                'account_number' => '2001234561',
            ],
        );
    }

    #[Test]
    public function an_expenses_own_business_needs_no_address_and_keeps_its_optional_customer_fields(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $addressQuery = Address::where('business_id', $business->id);
        $addressQuery->delete();
        $data = $this->expenseInput($business);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'expenses',
            [
                'reference_number' => $data['reference_number'],
                'business_id' => $business->id,
                'customer_name' => $business->name,
                'customer_street' => null,
                'customer_city' => null,
                'supplier_name' => 'One-off supplier',
            ],
        );
    }

    #[Test]
    public function a_new_contact_and_its_account_are_copied_and_typed_values_are_ignored(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $addressFactory = Address::factory();
        $address = $addressFactory->create(['business_id' => null, 'contact_id' => $contact->id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create();
        $data = $this->expenseInput(
            $business,
            [
                'contact_id' => $contact->id,
                'bank_account_id' => $account->id,
                'supplier_country' => 'invalid',
                'account_iban' => 'invalid',
            ],
        );
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas(
            'expenses',
            [
                'reference_number' => $data['reference_number'],
                'supplier_name' => $contact->name,
                'supplier_street' => $address->street,
                'account_number' => $account->number,
            ],
        );
    }

    #[Test]
    public function keeping_the_contact_and_account_preserves_the_account_snapshot_and_saves_document_corrections(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id, 'archived_at' => now()]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create(['archived_at' => now()]);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'bank_account_id' => $account->id,
                'account_number' => '2001234561',
                'account_bank_code' => '2010',
                'account_country' => 'CZ',
            ],
        );
        $httpResponse = $this->put(
            '/admin/expense/' . $expense->id,
            $this->expenseInput(
                $business,
                [
                    'contact_id' => $contact->id,
                    'bank_account_id' => $account->id,
                    'supplier_name' => 'Document correction',
                ],
            ),
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertSame('Document correction', $expense->fresh()->supplier_name);
        $this->assertSame('2001234561', $expense->fresh()->account_number);
        $this->assertSame($contact->name, $contact->fresh()->name);
    }

    #[Test]
    public function a_changed_contact_replaces_the_supplier(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(
            ['user_id' => $this->adminUser->id, 'business_id' => $business->id],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => null, 'contact_id' => $contact->id]);
        $httpResponse = $this->put(
            '/admin/expense/' . $expense->id,
            $this->expenseInput($business, ['contact_id' => $contact->id]),
        );
        $httpResponse->assertSessionHasNoErrors();
        $this->assertSame($contact->name, $expense->fresh()->supplier_name);
    }

    #[Test]
    public function an_account_of_a_different_contact_is_rejected(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy(
            $contactFactory->create(['user_id' => $this->adminUser->id]),
        );
        $account = $bankAccountFactory->create();
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput(
                $business,
                ['contact_id' => $contact->id, 'bank_account_id' => $account->id],
            ),
        );
        $httpResponse->assertSessionHasErrors('bank_account_id');
    }

    #[Test]
    public function another_owners_business_is_rejected(): void
    {
        [$business] = $this->issuerOf($this->regularUser);
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput($business, ['user_id' => $this->adminUser->id]),
        );
        $httpResponse->assertSessionHasErrors('business_id');
    }

    #[Test]
    public function newly_selected_archived_contacts_are_rejected(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id, 'archived_at' => now()]);
        $httpResponse = $this->post(
            '/admin/expense',
            $this->expenseInput($business, ['contact_id' => $contact->id]),
        );
        $httpResponse->assertSessionHasErrors('contact_id');
    }

    #[Test]
    public function editing_keeps_the_stored_owner_whatever_owner_is_submitted(): void
    {
        [$business] = $this->issuerOf($this->regularUser);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(['user_id' => $this->regularUser->id]);
        $httpResponse = $this->put(
            '/admin/expense/' . $expense->id,
            $this->expenseInput(
                $business,
                [
                    'user_id' => $this->adminUser->id,
                    'description' => 'Corrected description',
                ],
            ),
        );
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $stored = $expense->fresh();
        $this->assertSame($this->regularUser->id, $stored->user_id);
        $this->assertSame('Corrected description', $stored->description);
    }

    #[Test]
    public function an_owner_without_a_plan_role_gets_no_expense_whatever_the_admin_may_do(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        [$business] = $this->issuerOf($owner);
        $data = $this->expenseInput($business);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse->assertForbidden();
        $this->assertDatabaseMissing('expenses', ['reference_number' => $data['reference_number']]);
    }

    #[Test]
    public function an_update_for_an_owner_without_a_plan_role_is_refused(): void
    {
        $userFactory = User::factory();
        $owner = $userFactory->create();
        [$business] = $this->issuerOf($owner);
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(
            ['user_id' => $owner->id, 'description' => 'Kept'],
        );
        $httpResponse = $this->put(
            '/admin/expense/' . $expense->id,
            $this->expenseInput(
                $business,
                ['description' => 'Changed'],
            ),
        );
        $httpResponse->assertForbidden();
        $this->assertSame('Kept', $expense->fresh()->description);
    }

    #[Test]
    public function an_expense_an_admin_creates_counts_against_the_owners_quota(): void
    {
        $role = Role::findByName('frontend_user', 'web');
        $roleLimitQuery = RoleLimit::where('role_id', $role->id);
        $roleLimitQuery->update(['expenses' => 1]);
        [$business] = $this->issuerOf($this->regularUser);
        $data = $this->expenseInput($business);
        $httpResponse = $this->post('/admin/expense', $data);
        $httpResponse = $httpResponse->assertSessionHasNoErrors();
        $httpResponse->assertRedirect();
        $this->assertDatabaseHas('expenses', ['reference_number' => $data['reference_number']]);
        $httpResponse = $this->post(
            '/admin/expense',
            array_replace($data, ['reference_number' => 'blocked']),
        );
        $httpResponse->assertRedirect();
        $this->assertDatabaseMissing('expenses', ['reference_number' => 'blocked']);
    }

    #[Test]
    public function a_failed_expense_keeps_a_deduplicated_attachment_and_removes_only_new_files(): void
    {
        Storage::fake('public');
        [$business] = $this->issuerOf($this->adminUser);
        $data = $this->expenseInput($business);
        $receipt = '%PDF-1.4 shared receipt';
        $fileFactory = UploadedFile::fake();
        $httpResponse = $this->post(
            '/admin/expense',
            $data + [
                'attachments' => [$fileFactory->createWithContent('receipt.pdf', $receipt)],
            ],
        );
        $httpResponse->assertSessionHasNoErrors();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $path = $expense->attachments[0];
        $this->assertDatabaseHas('file_hashes', ['path' => $path, 'ref_count' => 1]);
        $this->mock(
            ExpenseWriteRepositoryInterface::class,
            function (MockInterface $mock): void {
                $expectation = $mock->shouldReceive('create');
                $expectation->once();
                $expectation->andThrow(
                    new RuntimeException('Persistence failed'),
                );
            },
        );
        $this->withoutExceptionHandling();
        try {
            $this->post(
                '/admin/expense',
                array_replace(
                    $data,
                    [
                        'reference_number' => 'FAILED',
                        'attachments' => [
                            $fileFactory->createWithContent('receipt.pdf', $receipt),
                            $fileFactory->createWithContent('new.pdf', '%PDF-1.4 new receipt'),
                        ],
                    ],
                ),
            );
            $this->fail('The expense write did not fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Persistence failed', $exception->getMessage());
        }
        $disk = Storage::disk('public');
        $disk->assertExists($path);
        $this->assertSame(
            [$path],
            $disk->allFiles('expenses/attachments'),
        );
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
        $fileFactory = UploadedFile::fake();
        $httpResponse = $this->post(
            '/admin/expense',
            $data + ['attachments' => [$fileFactory->create(
                'receipt.pdf',
                12,
                'application/pdf',
            )]],
        );
        $httpResponse->assertSessionHasNoErrors();
        $expenseQuery = Expense::where('reference_number', $data['reference_number']);
        $expense = $expenseQuery->sole();
        $path = $expense->attachments[0];
        $disk = Storage::disk('public');
        $disk->assertExists($path);
        $httpResponse = $this->get('/admin/expense/' . $expense->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee(
            basename($path),
        );
        $httpResponse->assertSee('la-file-pdf');
        $httpResponse = $this->put('/admin/expense/' . $expense->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $this->assertSame([$path], $expense->fresh()->attachments);
        $disk->put('someone-else.pdf', 'private');
        $httpResponse = $this->put(
            '/admin/expense/' . $expense->id,
            $data + ['attachments_removed' => json_encode([$path, 'someone-else.pdf'])],
        );
        $httpResponse->assertSessionHasNoErrors();
        $this->assertSame([], $expense->fresh()->attachments);
        $disk->assertMissing($path);
        $disk->assertExists('someone-else.pdf');
    }

    #[Test]
    public function show_and_list_render_the_saved_supplier(): void
    {
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(['supplier_name' => 'Saved supplier']);
        $httpResponse = $this->get('/admin/expense/' . $expense->id . '/show');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse->assertSee('Saved supplier');
        $httpResponse = $this->get('/admin/expense');
        $httpResponse->assertOk();
    }

    #[Test]
    public function guests_and_users_without_permission_cannot_write_expenses(): void
    {
        $auth = auth('backpack');
        $auth->logout();
        $httpResponse = $this->post('/admin/expense', []);
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $httpResponse = $httpClient->post('/admin/expense', []);
        $httpResponse->assertForbidden();
        $httpResponse = $this->get('/admin/expense/create');
        $httpResponse->assertForbidden();
    }
}
