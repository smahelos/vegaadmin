<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsExpenseInput;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class AdminDocumentSnapshotsTest extends TestCase
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

    /** @return array<string, array{string, string}> */
    public static function documents(): array
    {
        return ['invoice' => ['invoice', 'customer'], 'expense' => ['expense', 'supplier']];
    }

    #[Test]
    #[DataProvider('documents')]
    public function changing_the_counterparty_identity_requires_another_contact_or_a_one_off_party(string $type, string $role): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create([
            'user_id' => $this->adminUser->id,
            'registration_country' => 'DE',
            'company_number' => 'HRB 123',
        ]);
        $model = $type === 'invoice' ? Invoice::class : Expense::class;
        $document = $model::factory()->create([
            'user_id' => $this->adminUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            $role . '_registration_country' => 'DE',
            $role . '_company_number' => 'HRB 123',
        ]);
        $data = $type === 'invoice'
            ? $this->invoiceInput(
                $business,
                $account,
                ['invoice_vs' => $document->invoice_vs, 'payment_amount' => '0'],
            )
            : $this->expenseInput($business);
        $data['user_id'] = $this->adminUser->id;
        $data['contact_id'] = $contact->id;
        $data[$role . '_registration_country'] = 'DE';
        $data[$role . '_company_number'] = 'HRB 999';

        $this->put('/admin/' . $type . '/' . $document->id, $data)->assertSessionHasErrors($role . '_company_number');
        $this->assertSame('HRB 123', $document->fresh()->getAttribute($role . '_company_number'));
        $this->assertSame($contact->id, $document->fresh()->contact_id);

        $data['contact_id'] = null;
        $this->put('/admin/' . $type . '/' . $document->id, $data)->assertSessionHasNoErrors();
        $this->assertSame('HRB 999', $document->fresh()->getAttribute($role . '_company_number'));
        $this->assertNull($document->fresh()->contact_id);
    }

    #[Test]
    #[DataProvider('documents')]
    public function a_contact_identity_correction_preserves_the_documents_historical_identity(string $type, string $role): void
    {
        [$business, $account] = $this->issuerOf($this->adminUser);
        $contact = Contact::factory()->create([
            'user_id' => $this->adminUser->id,
            'registration_country' => 'DE',
            'company_number' => 'HRB 999',
            'archived_at' => now(),
        ]);
        $model = $type === 'invoice' ? Invoice::class : Expense::class;
        $document = $model::factory()->create([
            'user_id' => $this->adminUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            $role . '_registration_country' => 'DE',
            $role . '_company_number' => 'HRB 123',
        ]);
        $data = $type === 'invoice'
            ? $this->invoiceInput(
                $business,
                $account,
                ['invoice_vs' => $document->invoice_vs, 'payment_amount' => '0'],
            )
            : $this->expenseInput($business);
        $data['user_id'] = $this->adminUser->id;
        $data['contact_id'] = $contact->id;
        $data[$role . '_registration_country'] = 'DE';
        $data[$role . '_company_number'] = 'HRB 123';
        $data[$role . '_name'] = 'Document-only correction';

        $this->put('/admin/' . $type . '/' . $document->id, $data)->assertSessionHasNoErrors();

        $this->assertSame('HRB 123', $document->fresh()->getAttribute($role . '_company_number'));
        $this->assertSame('Document-only correction', $document->fresh()->getAttribute($role . '_name'));
        $this->assertSame($contact->id, $document->fresh()->contact_id);
        $this->assertSame('HRB 999', $contact->fresh()->company_number);
    }

    #[Test]
    #[DataProvider('documents')]
    public function opening_edit_keeps_the_document_snapshot_even_when_its_sources_are_archived(string $type, string $role): void
    {
        $business = Business::factory()->create(['user_id' => $this->adminUser->id, 'name' => 'Changed business source']);
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id, 'name' => 'Changed contact source']);
        $accountOwner = $type === 'invoice' ? $business : $contact;
        $account = BankAccount::factory()->ownedBy($accountOwner)->create(['archived_at' => now()]);
        $business->update(['archived_at' => now()]);
        $contact->update(['archived_at' => now()]);
        $model = $type === 'invoice' ? Invoice::class : Expense::class;
        $businessRole = $role === 'customer' ? 'supplier' : 'customer';
        $document = $model::factory()->create([
            'user_id' => $this->adminUser->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
            $role . '_name' => 'Saved counterparty correction',
            $role . '_street' => 'Saved street 12',
            $role . '_email' => 'saved@example.test',
            $businessRole . '_name' => 'Saved business snapshot',
            'account_number' => '2001234561',
        ]);

        $this->get('/admin/' . $type . '/' . $document->id . '/edit')->assertOk()
            ->assertSee('value="Saved counterparty correction"', false)
            ->assertSee('value="Saved street 12"', false)
            ->assertSee('value="saved@example.test"', false)
            ->assertSee('Saved business snapshot')
            ->assertSee('value="2001234561"', false)
            ->assertSee('data-stored-reference-id="' . $contact->id . '"', false);
        $this->assertSame('Saved counterparty correction', $document->fresh()->getAttribute($role . '_name'));
        $this->assertSame('Changed contact source', $contact->fresh()->name);
    }

    #[Test]
    #[DataProvider('documents')]
    public function a_validation_redirect_keeps_the_stored_reference_separate_from_the_new_selection(string $type, string $role): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->adminUser->id]);
        $changed = Contact::factory()->create(['user_id' => $this->adminUser->id]);
        $model = $type === 'invoice' ? Invoice::class : Expense::class;
        $document = $model::factory()->create(['user_id' => $this->adminUser->id, 'contact_id' => $contact->id]);

        $this->withSession(['_old_input' => ['contact_id' => $changed->id, $role . '_name' => 'Typed before validation']])
            ->get('/admin/' . $type . '/' . $document->id . '/edit')->assertOk()
            ->assertSee('value="Typed before validation"', false)
            ->assertSee('<option value="' . $changed->id . '" selected>' . $changed->name . '</option>', false)
            ->assertSee('data-stored-reference-id="' . $contact->id . '"', false);
    }
}
