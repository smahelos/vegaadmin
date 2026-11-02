<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\DocumentPartyPicker;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class DocumentPartyPickerTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
    }

    #[Test]
    public function a_new_invoice_shows_the_data_of_the_selected_business(): void
    {
        $business = $this->businessWithAddress(['name' => 'Moje firma s.r.o.']);

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class, ['businessId' => $business->id]);
        $component->assertSet('businessId', $business->id);
        $component->assertSee('Moje firma s.r.o.');
        $component->assertSee('Brno');
        $component->assertDontSee(
            __('parties.validation.business_address_incomplete'),
        );
    }

    #[Test]
    public function a_business_without_a_complete_address_is_flagged(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class, ['businessId' => $business->id]);
        $component->assertSee(
            __('parties.validation.business_address_incomplete'),
        );
    }

    #[Test]
    public function a_stored_invoice_shows_its_business_copy_until_the_user_reloads_it(): void
    {
        $business = $this->businessWithAddress(['name' => 'Aktuální název']);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'supplier_name' => 'Uložený název',
            ],
        );

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class, ['invoiceId' => $invoice->id]);
        $component->assertSee('Uložený název');
        $component->assertSee(
            __('invoices.messages.business_changed'),
        );
        $component->call('reloadBusinessData');
        $component->assertSet('reloadBusiness', true);
        $component->assertDontSee('Uložený název');
        $component->assertSee(
            __('invoices.messages.business_reloaded'),
        );
    }

    #[Test]
    public function the_selected_account_shows_its_data(): void
    {
        $business = $this->businessWithAddress();
        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($business);
        $ownedFactory->create(
            [
                'number' => '2001234561',
                'bank_code' => '2010',
                'iban' => 'CZ1520100000002001234561',
                'swift' => 'FIOBCZPP',
                'bank_name' => 'Fio banka',
            ],
        );
        $account = $ownedFactory->create(
            [
                'number' => '1234567899',
                'bank_code' => '0100',
                'iban' => 'CZ9501000000001234567899',
                'swift' => 'KOMBCZPP',
                'bank_name' => 'Komerční banka',
            ],
        );

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class, ['businessId' => $business->id]);
        $component->set('bankAccountId', $account->id);
        $component->assertSee('1234567899/0100');
        $component->assertSee('CZ95 0100 0000 0012 3456 7899');
        $component->assertSee('KOMBCZPP');
        $component->assertSee('Komerční banka');
        $component->assertDontSee('Fio banka');
    }

    #[Test]
    public function a_stored_invoice_shows_its_account_copy_until_the_user_reloads_the_business(): void
    {
        $business = $this->businessWithAddress();
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $account = $bankAccountFactory->create(['bank_name' => 'Aktuální banka']);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
                'bank_account_id' => $account->id,
                'account_iban' => $account->iban,
                'account_number' => $account->number,
                'account_bank_code' => $account->bank_code,
                'account_country' => 'CZ',
                'account_swift' => $account->swift,
                'account_bank_name' => 'Uložená banka',
            ],
        );

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class, ['invoiceId' => $invoice->id]);
        $component->assertSee('Uložená banka');
        $component->call('reloadBusinessData');
        $component->assertDontSee('Uložená banka');
        $component->assertSee('Aktuální banka');
    }

    #[Test]
    public function selecting_a_contact_fills_the_customer(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Odběratel s.r.o.',
            ],
        );

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class);
        $component->call('selectContact', $contact->id);
        $component->assertHasNoErrors();
        $component->assertSet('contactId', $contact->id);
        $component->assertSet('customer.name', 'Odběratel s.r.o.');
        $component->assertSet('customer.companyNumber', $contact->company_number);
    }

    #[Test]
    public function an_archived_contact_cannot_be_selected(): void
    {
        $contact = Contact::factory()->archived()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class);
        $component->call('selectContact', $contact->id);
        $component->assertHasErrors('contactId');
        $component->assertSet('contactId', null);
    }

    #[Test]
    public function the_contact_search_lists_only_the_users_contacts(): void
    {
        $contactFactory = Contact::factory();
        $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Hledaný vlastní',
            ],
        );
        $contactFactory->create(['name' => 'Hledaný cizí']);

        Livewire::actingAs($this->user);
        $component = Livewire::test(DocumentPartyPicker::class);
        $component->set('contactQuery', 'Hledaný');
        $component->assertSee('Hledaný vlastní');
        $component->assertDontSee('Hledaný cizí');
    }

    #[Test]
    public function a_guest_enters_the_supplier_and_its_account(): void
    {
        $component = Livewire::test(DocumentPartyPicker::class, ['guest' => true]);
        $component->assertOk();
        $component->assertSee(
            __('invoices.sections.payment_info'),
        );
        $component->assertDontSee(
            __('invoices.placeholders.select_business'),
        );
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function businessWithAddress(array $attributes = []): Business
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id, ...$attributes]);
        $addressFactory = Address::factory();
        $addressFactory->create([
            'business_id' => $business->id,
            'city' => 'Brno',
        ]);

        return $business;
    }
}
