<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Invoice\Services;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Application\Invoice\Services\InvoicePartiesAssembler;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\Exceptions\ArchivedReferenceSelectedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\ContactIdentityChangedException;
use App\Domain\Party\Exceptions\IncompleteBusinessAddressException;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class InvoicePartiesAssemblerTest extends TestCase
{
    use BuildsParties;

    private const CUSTOMER_NUMBER = '27082440';

    private const OTHER_NUMBER = '45317054';

    private User $user;

    private InvoicePartiesAssembler $assembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->frontendUser();
        $this->assembler = $this->app->make(InvoicePartiesAssembler::class);
    }

    #[Test]
    public function a_new_invoice_copies_the_business_and_its_account(): void
    {
        $business = $this->businessWithAddress(['name' => 'Moje firma s.r.o.']);
        $account = BankAccount::factory()->ownedBy($business)->create();

        $parties = $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, account: $account),
            null,
        );

        $this->assertSame('Moje firma s.r.o.', $parties->supplier->name);
        $this->assertSame('Brno', $parties->supplier->address?->city);
        $this->assertSame($account->number, $parties->account?->account->number);
        $this->assertSame('Odběratel s.r.o.', $parties->customer->name);
        $this->assertNull($parties->contactId);
    }

    #[Test]
    public function a_stored_invoice_keeps_its_business_copy_until_the_user_reloads_it(): void
    {
        $business = $this->businessWithAddress(['name' => 'Původní název']);
        $stored = $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business),
            null,
        );
        $business->update(['name' => 'Nový název']);

        $kept = $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business),
            $stored,
        );
        $reloaded = $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, reloadBusiness: true),
            $stored,
        );

        $this->assertSame('Původní název', $kept->supplier->name);
        $this->assertSame('Nový název', $reloaded->supplier->name);
    }

    #[Test]
    public function an_archived_business_cannot_issue_a_new_invoice(): void
    {
        $business = $this->businessWithAddress();
        $business->update(['archived_at' => now()]);

        $this->expectException(ArchivedReferenceSelectedException::class);

        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business),
            null,
        );
    }

    #[Test]
    public function a_business_without_a_complete_address_cannot_issue_an_invoice(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);

        $this->expectException(IncompleteBusinessAddressException::class);

        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business),
            null,
        );
    }

    #[Test]
    public function another_users_business_is_not_found(): void
    {
        $foreign = Business::factory()->create();
        Address::factory()->create(['business_id' => $foreign->id]);

        $this->expectException(BusinessNotFoundException::class);

        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($foreign),
            null,
        );
    }

    #[Test]
    public function an_account_of_another_business_is_not_found(): void
    {
        $business = $this->businessWithAddress();
        $otherBusiness = Business::factory()->create(['user_id' => $this->user->id]);
        $account = BankAccount::factory()->ownedBy($otherBusiness)->create();

        $this->expectException(BankAccountNotFoundException::class);

        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, account: $account),
            null,
        );
    }

    #[Test]
    public function a_contact_is_not_linked_to_a_customer_with_another_company_number(): void
    {
        $business = $this->businessWithAddress();
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'company_number' => self::OTHER_NUMBER,
        ]);

        $this->expectException(ContactIdentityChangedException::class);

        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, contact: $contact),
            null,
        );
    }

    #[Test]
    public function a_one_off_customer_is_saved_to_the_contacts_when_asked(): void
    {
        $business = $this->businessWithAddress();

        $parties = $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, saveCustomerToContacts: true),
            null,
        );

        $contact = Contact::query()->where('user_id', $this->user->id)->sole();
        $this->assertSame($contact->id, $parties->contactId?->value);
        $this->assertSame('Odběratel s.r.o.', $contact->name);
        $this->assertSame(self::CUSTOMER_NUMBER, $contact->company_number);
    }

    #[Test]
    public function archived_contact_and_account_keep_the_working_documents_copies(): void
    {
        $business = $this->businessWithAddress();
        $account = BankAccount::factory()->ownedBy($business)->create();
        $contact = Contact::factory()->create(['user_id' => $this->user->id, 'company_number' => self::CUSTOMER_NUMBER]);
        $selection = $this->selection(
            $business,
            account: $account,
            contact: $contact,
        );
        $stored = $this->assembler->assemble(
            $this->idOf($this->user),
            $selection,
            null,
        );
        $account->update(['archived_at' => now(), 'bank_name' => 'Changed source bank']);
        $contact->update(['archived_at' => now(), 'name' => 'Changed source contact']);

        $kept = $this->assembler->assemble(
            $this->idOf($this->user),
            $selection,
            $stored,
        );
        $this->assertSame($stored->account, $kept->account);
        $this->assertSame($stored->customer, $kept->customer);
        $this->assertSame($account->id, $kept->bankAccountId?->value);
        $this->assertSame($contact->id, $kept->contactId?->value);
        $this->assertNotNull($account->fresh()->archived_at);
        $this->assertNotNull($contact->fresh()->archived_at);
    }

    #[Test]
    public function a_newly_selected_archived_account_is_refused_even_when_its_business_is_active(): void
    {
        $business = $this->businessWithAddress();
        $account = BankAccount::factory()->ownedBy($business)->create(['archived_at' => now()]);
        $this->expectException(ArchivedReferenceSelectedException::class);
        $this->assembler->assemble(
            $this->idOf($this->user),
            $this->selection($business, account: $account),
            null,
        );
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

    private function selection(
        Business $business,
        ?BankAccount $account = null,
        ?Contact $contact = null,
        bool $saveCustomerToContacts = false,
        bool $reloadBusiness = false,
    ): InvoicePartySelection {
        return new InvoicePartySelection(
            BusinessId::fromInt($business->id),
            $account === null ? null : BankAccountId::fromInt($account->id),
            $contact === null ? null : ContactId::fromInt($contact->id),
            $this->customer(),
            $saveCustomerToContacts,
            $reloadBusiness,
        );
    }

    private function customer(): DocumentPartyDTO
    {
        return new DocumentPartyDTO(
            'Odběratel s.r.o.',
            RegistrationNumber::fromParts(Country::CZ, self::CUSTOMER_NUMBER),
            null,
            $this->brnoAddress(),
            null,
            null,
        );
    }
}
