<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class DocumentPartyCrudControllerTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    #[Test]
    public function contact_search_returns_the_document_profile_including_the_address(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->regularUser->id,
            'name' => 'Profile for the document',
            'registration_country' => 'AT',
            'company_number' => 'FN 123456',
            'vat_number' => 'ATU12345678',
            'email' => 'billing@example.test',
            'phone' => '+43123456789',
            'note' => 'Private note',
            'contact_person' => 'Private contact person',
        ]);
        $address = Address::factory()->create([
            'business_id' => null,
            'contact_id' => $contact->id,
            'street' => 'Ring 12',
            'city' => 'Wien',
            'postal_code' => '1010',
            'country' => 'AT',
        ]);

        $response = $this->getJson(route('admin.document-parties.contacts', ['user_id' => $this->regularUser->id]));

        $response->assertOk()->assertJsonPath('data.0.profile', [
            'name' => $contact->name,
            'registration_country' => 'AT',
            'company_number' => 'FN 123456',
            'vat_number' => 'ATU12345678',
            'street' => $address->street,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'country' => 'AT',
            'email' => $contact->email,
            'phone' => $contact->phone,
        ])->assertDontSee('Private note')->assertDontSee('Private contact person');
    }

    #[Test]
    public function offers_only_the_owners_active_businesses_and_contacts(): void
    {
        $business = Business::factory()->create(['user_id' => $this->regularUser->id]);
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        Business::factory()->create(['user_id' => $this->regularUser->id, 'archived_at' => now()]);
        Contact::factory()->create(['user_id' => $this->regularUser->id, 'archived_at' => now()]);
        Business::factory()->create(['user_id' => $this->adminUser->id]);
        Contact::factory()->create(['user_id' => $this->adminUser->id]);
        $this->getJson(route('admin.document-parties.businesses', ['user_id' => $this->regularUser->id]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $business->id);
        $this->getJson(route('admin.document-parties.contacts', ['user_id' => $this->regularUser->id]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $contact->id);
    }

    #[Test]
    public function accounts_are_scoped_to_the_owner_and_business_and_identify_the_default(): void
    {
        $business = Business::factory()->create(['user_id' => $this->regularUser->id]);
        $account = BankAccount::factory()->ownedBy($business)->create();
        $business->update(['default_bank_account_id' => $account->id]);
        BankAccount::factory()->ownedBy($business)->create(['archived_at' => now()]);
        $this->getJson(route('admin.document-parties.accounts', ['user_id' => $this->regularUser->id, 'business_id' => $business->id]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $account->id)->assertJsonPath('data.0.is_default', true);
        $this->getJson(route('admin.document-parties.accounts', ['user_id' => $this->adminUser->id, 'business_id' => $business->id]))->assertOk()->assertJsonCount(0, 'data');
    }

    #[Test]
    public function expense_accounts_come_only_from_the_selected_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->regularUser->id]);
        $account = BankAccount::factory()->ownedBy($contact)->create();
        BankAccount::factory()->ownedBy($contact)->create(['archived_at' => now()]);
        $this->getJson(route('admin.document-parties.accounts', ['user_id' => $this->regularUser->id, 'contact_id' => $contact->id]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $account->id);
    }

    #[Test]
    public function selector_parameters_are_validated(): void
    {
        $this->getJson(route('admin.document-parties.businesses', ['user_id' => 'invalid', 'q' => ['invalid']]))
            ->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'q']);
    }

    #[Test]
    public function guests_and_users_without_document_permissions_cannot_search(): void
    {
        auth('backpack')->logout();
        $this->getJson(route('admin.document-parties.businesses'))->assertUnauthorized();
        $this->actingAs($this->regularUser, 'backpack');
        foreach ([
            'businesses',
            'contacts',
            'accounts',
        ] as $source) {
            $this->getJson(route('admin.document-parties.' . $source))->assertForbidden();
        }
    }
}
