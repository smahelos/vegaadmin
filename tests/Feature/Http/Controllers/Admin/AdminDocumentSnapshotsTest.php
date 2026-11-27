<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
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

    #[Test]
    public function changing_the_counterparty_identity_requires_another_contact_or_a_one_off_party(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'registration_country' => 'DE',
                'company_number' => 'HRB 123',
            ],
        );
        $modelFactory = Expense::factory();
        $document = $modelFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'supplier_registration_country' => 'DE',
                'supplier_company_number' => 'HRB 123',
            ],
        );
        $data = $this->expenseInput($business);
        $data['user_id'] = $this->adminUser->id;
        $data['contact_id'] = $contact->id;
        $data['supplier_registration_country'] = 'DE';
        $data['supplier_company_number'] = 'HRB 999';

        $httpResponse = $this->put('/admin/expense/' . $document->id, $data);
        $httpResponse->assertSessionHasErrors('supplier_company_number');
        $freshDocument = $document->fresh();
        $this->assertSame(
            'HRB 123',
            $freshDocument->getAttribute('supplier_company_number'),
        );
        $this->assertSame($contact->id, $freshDocument->contact_id);

        $data['contact_id'] = null;
        $httpResponse = $this->put('/admin/expense/' . $document->id, $data);
        $httpResponse->assertSessionHasNoErrors();
        $freshDocument = $document->fresh();
        $this->assertSame(
            'HRB 999',
            $freshDocument->getAttribute('supplier_company_number'),
        );
        $this->assertNull($freshDocument->contact_id);
    }

    #[Test]
    public function a_contact_identity_correction_preserves_the_documents_historical_identity(): void
    {
        [$business] = $this->issuerOf($this->adminUser);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'registration_country' => 'DE',
                'company_number' => 'HRB 999',
                'archived_at' => now(),
            ],
        );
        $modelFactory = Expense::factory();
        $document = $modelFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'supplier_registration_country' => 'DE',
                'supplier_company_number' => 'HRB 123',
            ],
        );
        $data = $this->expenseInput($business);
        $data['user_id'] = $this->adminUser->id;
        $data['contact_id'] = $contact->id;
        $data['supplier_registration_country'] = 'DE';
        $data['supplier_company_number'] = 'HRB 123';
        $data['supplier_name'] = 'Document-only correction';

        $httpResponse = $this->put('/admin/expense/' . $document->id, $data);
        $httpResponse->assertSessionHasNoErrors();

        $freshDocument = $document->fresh();
        $this->assertSame(
            'HRB 123',
            $freshDocument->getAttribute('supplier_company_number'),
        );
        $this->assertSame(
            'Document-only correction',
            $freshDocument->getAttribute('supplier_name'),
        );
        $this->assertSame($contact->id, $freshDocument->contact_id);
        $this->assertSame('HRB 999', $contact->fresh()->company_number);
    }

    #[Test]
    public function opening_edit_keeps_the_document_snapshot_even_when_its_sources_are_archived(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(
            ['user_id' => $this->adminUser->id, 'name' => 'Changed business source'],
        );
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            ['user_id' => $this->adminUser->id, 'name' => 'Changed contact source'],
        );
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create(['archived_at' => now()]);
        $business->update(['archived_at' => now()]);
        $contact->update(['archived_at' => now()]);
        $modelFactory = Expense::factory();
        $document = $modelFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'bank_account_id' => $account->id,
                'supplier_name' => 'Saved counterparty correction',
                'supplier_street' => 'Saved street 12',
                'supplier_email' => 'saved@example.test',
                'customer_name' => 'Saved business snapshot',
                'account_number' => '2001234561',
            ],
        );

        $httpResponse = $this->get('/admin/expense/' . $document->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('value="Saved counterparty correction"', false);
        $httpResponse = $httpResponse->assertSee('value="Saved street 12"', false);
        $httpResponse = $httpResponse->assertSee('value="saved@example.test"', false);
        $httpResponse = $httpResponse->assertSee('Saved business snapshot');
        $httpResponse = $httpResponse->assertSee('value="2001234561"', false);
        $httpResponse->assertSee('data-stored-reference-id="' . $contact->id . '"', false);
        $freshDocument = $document->fresh();
        $this->assertSame(
            'Saved counterparty correction',
            $freshDocument->getAttribute('supplier_name'),
        );
        $this->assertSame('Changed contact source', $contact->fresh()->name);
    }

    #[Test]
    public function a_validation_redirect_keeps_the_stored_reference_separate_from_the_new_selection(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->adminUser->id]);
        $changed = $contactFactory->create(
            [
                'user_id' => $this->adminUser->id,
                'name' => "O'Conner & Sons <Trading>",
            ],
        );
        $modelFactory = Expense::factory();
        $document = $modelFactory->create(
            ['user_id' => $this->adminUser->id, 'contact_id' => $contact->id],
        );

        $httpClient = $this->withSession(
            ['_old_input' => ['contact_id' => $changed->id, 'supplier_name' => 'Typed before validation']],
        );
        $httpResponse = $httpClient->get('/admin/expense/' . $document->id . '/edit');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('value="Typed before validation"', false);
        $httpResponse = $httpResponse->assertSee(
            '<option value="' . $changed->id . '" selected>' . e($changed->name) . '</option>',
            false,
        );
        $httpResponse->assertSee('data-stored-reference-id="' . $contact->id . '"', false);
    }
}
