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

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class, ['businessId' => $business->id])
            ->assertSet('businessId', $business->id)
            ->assertSee('Moje firma s.r.o.')
            ->assertSee('Brno')
            ->assertDontSee(__('parties.validation.business_address_incomplete'));
    }

    #[Test]
    public function a_business_without_a_complete_address_is_flagged(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class, ['businessId' => $business->id])
            ->assertSee(__('parties.validation.business_address_incomplete'));
    }

    #[Test]
    public function a_stored_invoice_shows_its_business_copy_until_the_user_reloads_it(): void
    {
        $business = $this->businessWithAddress(['name' => 'Aktuální název']);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'supplier_name' => 'Uložený název',
        ]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class, ['invoiceId' => $invoice->id])
            ->assertSee('Uložený název')
            ->assertSee(__('invoices.messages.business_changed'))
            ->call('reloadBusinessData')
            ->assertSet('reloadBusiness', true)
            ->assertDontSee('Uložený název')
            ->assertSee(__('invoices.messages.business_reloaded'));
    }

    #[Test]
    public function the_selected_account_shows_its_data(): void
    {
        $business = $this->businessWithAddress();
        BankAccount::factory()->ownedBy($business)->create([
            'number' => '2001234561',
            'bank_code' => '2010',
            'iban' => 'CZ1520100000002001234561',
            'swift' => 'FIOBCZPP',
            'bank_name' => 'Fio banka',
        ]);
        $account = BankAccount::factory()->ownedBy($business)->create([
            'number' => '1234567899',
            'bank_code' => '0100',
            'iban' => 'CZ9501000000001234567899',
            'swift' => 'KOMBCZPP',
            'bank_name' => 'Komerční banka',
        ]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class, ['businessId' => $business->id])
            ->set('bankAccountId', $account->id)
            ->assertSee('1234567899/0100')
            ->assertSee('CZ95 0100 0000 0012 3456 7899')
            ->assertSee('KOMBCZPP')
            ->assertSee('Komerční banka')
            ->assertDontSee('Fio banka');
    }

    #[Test]
    public function a_stored_invoice_shows_its_account_copy_until_the_user_reloads_the_business(): void
    {
        $business = $this->businessWithAddress();
        $account = BankAccount::factory()->ownedBy($business)->create(['bank_name' => 'Aktuální banka']);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $business->id,
            'bank_account_id' => $account->id,
            'account_iban' => $account->iban,
            'account_number' => $account->number,
            'account_bank_code' => $account->bank_code,
            'account_country' => 'CZ',
            'account_swift' => $account->swift,
            'account_bank_name' => 'Uložená banka',
        ]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class, ['invoiceId' => $invoice->id])
            ->assertSee('Uložená banka')
            ->call('reloadBusinessData')
            ->assertDontSee('Uložená banka')
            ->assertSee('Aktuální banka');
    }

    #[Test]
    public function selecting_a_contact_fills_the_customer(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Odběratel s.r.o.',
        ]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class)
            ->call('selectContact', $contact->id)
            ->assertHasNoErrors()
            ->assertSet('contactId', $contact->id)
            ->assertSet('customer.name', 'Odběratel s.r.o.')
            ->assertSet('customer.companyNumber', $contact->company_number);
    }

    #[Test]
    public function an_archived_contact_cannot_be_selected(): void
    {
        $contact = Contact::factory()->archived()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class)
            ->call('selectContact', $contact->id)
            ->assertHasErrors('contactId')
            ->assertSet('contactId', null);
    }

    #[Test]
    public function the_contact_search_lists_only_the_users_contacts(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Hledaný vlastní',
        ]);
        Contact::factory()->create(['name' => 'Hledaný cizí']);

        Livewire::actingAs($this->user)
            ->test(DocumentPartyPicker::class)
            ->set('contactQuery', 'Hledaný')
            ->assertSee('Hledaný vlastní')
            ->assertDontSee('Hledaný cizí');
    }

    #[Test]
    public function a_guest_enters_the_supplier_and_its_account(): void
    {
        Livewire::test(DocumentPartyPicker::class, ['guest' => true])
            ->assertOk()
            ->assertSee(__('invoices.sections.payment_info'))
            ->assertDontSee(__('invoices.placeholders.select_business'));
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function businessWithAddress(array $attributes = []): Business
    {
        $business = Business::factory()->create(['user_id' => $this->user->id, ...$attributes]);
        Address::factory()->create([
            'business_id' => $business->id,
            'city' => 'Brno',
        ]);

        return $business;
    }
}
