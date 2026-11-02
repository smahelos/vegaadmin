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
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->regularUser->id,
                'name' => 'Profile for the document',
                'registration_country' => 'AT',
                'company_number' => 'FN 123456',
                'vat_number' => 'ATU12345678',
                'email' => 'billing@example.test',
                'phone' => '+43123456789',
                'logo' => 'parties/contact-logo.png',
                'note' => 'Private note',
                'contact_person' => 'Private contact person',
            ],
        );
        $addressFactory = Address::factory();
        $address = $addressFactory->create(
            [
                'business_id' => null,
                'contact_id' => $contact->id,
                'street' => 'Ring 12',
                'city' => 'Wien',
                'postal_code' => '1010',
                'country' => 'AT',
            ],
        );

        $response = $this->getJson(
            route('admin.document-parties.contacts', ['user_id' => $this->regularUser->id]),
        );

        $assertionResponse = $response->assertOk();
        $assertionResponse = $assertionResponse->assertJsonPath(
            'data.0.profile',
            [
                'logo_url' => asset('storage/parties/contact-logo.png'),
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
            ],
        );
        $assertionResponse = $assertionResponse->assertDontSee('Private note');
        $assertionResponse->assertDontSee('Private contact person');
    }

    #[Test]
    public function offers_only_the_owners_active_businesses_and_contacts(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->regularUser->id]);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $businessFactory2 = Business::factory();
        $businessFactory2->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $contactFactory2 = Contact::factory();
        $contactFactory2->create(
            ['user_id' => $this->regularUser->id, 'archived_at' => now()],
        );
        $businessFactory3 = Business::factory();
        $businessFactory3->create(['user_id' => $this->adminUser->id]);
        $contactFactory3 = Contact::factory();
        $contactFactory3->create(['user_id' => $this->adminUser->id]);
        $httpResponse = $this->getJson(
            route('admin.document-parties.businesses', ['user_id' => $this->regularUser->id]),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonCount(1, 'data');
        $httpResponse->assertJsonPath('data.0.id', $business->id);
        $httpResponse2 = $this->getJson(
            route('admin.document-parties.contacts', ['user_id' => $this->regularUser->id]),
        );
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2 = $httpResponse2->assertJsonCount(1, 'data');
        $httpResponse2->assertJsonPath('data.0.id', $contact->id);
    }

    #[Test]
    public function accounts_are_scoped_to_the_owner_and_business_and_identify_the_default(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->regularUser->id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $account = $bankAccountFactory->create();
        $business->update(['default_bank_account_id' => $account->id]);
        $bankAccountFactory2 = BankAccount::factory();
        $bankAccountFactory2 = $bankAccountFactory2->ownedBy($business);
        $bankAccountFactory2->create(['archived_at' => now()]);
        $httpResponse = $this->getJson(
            route(
                'admin.document-parties.accounts',
                ['user_id' => $this->regularUser->id, 'business_id' => $business->id],
            ),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonCount(1, 'data');
        $httpResponse = $httpResponse->assertJsonPath('data.0.id', $account->id);
        $httpResponse->assertJsonPath('data.0.is_default', true);
        $httpResponse2 = $this->getJson(
            route(
                'admin.document-parties.accounts',
                ['user_id' => $this->adminUser->id, 'business_id' => $business->id],
            ),
        );
        $httpResponse2 = $httpResponse2->assertOk();
        $httpResponse2->assertJsonCount(0, 'data');
    }

    #[Test]
    public function expense_accounts_come_only_from_the_selected_contact(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->regularUser->id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create();
        $bankAccountFactory2 = BankAccount::factory();
        $bankAccountFactory2 = $bankAccountFactory2->ownedBy($contact);
        $bankAccountFactory2->create(['archived_at' => now()]);
        $httpResponse = $this->getJson(
            route(
                'admin.document-parties.accounts',
                ['user_id' => $this->regularUser->id, 'contact_id' => $contact->id],
            ),
        );
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertJsonCount(1, 'data');
        $httpResponse->assertJsonPath('data.0.id', $account->id);
    }

    #[Test]
    public function selector_parameters_are_validated(): void
    {
        $httpResponse = $this->getJson(
            route('admin.document-parties.businesses', ['user_id' => 'invalid', 'q' => ['invalid']]),
        );
        $httpResponse = $httpResponse->assertUnprocessable();
        $httpResponse->assertJsonValidationErrors(['user_id', 'q']);
    }

    #[Test]
    public function guests_and_users_without_document_permissions_cannot_search(): void
    {
        auth('backpack')->logout();
        $httpResponse = $this->getJson(
            route('admin.document-parties.businesses'),
        );
        $httpResponse->assertUnauthorized();
        $this->actingAs($this->regularUser, 'backpack');
        foreach (
            [
                'businesses',
                'contacts',
                'accounts',
            ] as $source
        ) {
            $this->getJson(
                route('admin.document-parties.' . $source),
            )->assertForbidden();
        }
    }
}
