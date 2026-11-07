<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Domain\User\Enums\LimitedEntity;
use App\Livewire\Party\ContactForm;
use App\Models\Address;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class ContactFormTest extends TestCase
{
    use BuildsParties;

    private const VALID_COMPANY_NUMBER = '27082440';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function a_contact_needs_only_its_name(): void
    {
        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Jan Novák');
        $component->call('save');
        $component->assertHasNoErrors();
        $component->assertRedirect();

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $this->user->id);
        $contact = $contactQuery->sole();
        $this->assertNull($contact->email);
        $this->assertNull($contact->phone);
        $addressQuery = Address::query();
        $addressQuery->where('contact_id', $contact->id);
        $this->assertSame(
            0,
            $addressQuery->count(),
        );
    }

    #[Test]
    public function stores_a_foreign_company_with_its_own_registration_country(): void
    {
        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Wiener GmbH');
        $component->set('party.country', 'AT');
        $component->set('party.companyNumber', 'FN 123456a');
        $component->set('party.street', 'Ring 1');
        $component->set('party.city', 'Wien');
        $component->set('party.postalCode', '1010');
        $component->set('contactPerson', 'Anna Huber');
        $component->call('save');
        $component->assertHasNoErrors();

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $this->user->id);
        $contact = $contactQuery->sole();
        $this->assertSame('AT', $contact->registration_country);
        $this->assertSame('FN 123456a', $contact->company_number);
        $this->assertSame('Anna Huber', $contact->contact_person);
        $addressQuery = Address::query();
        $addressQuery->where('contact_id', $contact->id);
        $this->assertSame(
            'AT',
            $addressQuery->value('country'),
        );
    }

    #[Test]
    public function an_archived_contact_with_the_same_czech_company_number_blocks_a_copy(): void
    {
        $contactFactory = Contact::factory();
        $archivedFactory = $contactFactory->archived();
        $archivedFactory->create(
            [
                'user_id' => $this->user->id,
                'company_number' => self::VALID_COMPANY_NUMBER,
            ],
        );

        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Kopie');
        $component->set('party.registrationCountry', 'CZ');
        $component->set('party.companyNumber', self::VALID_COMPANY_NUMBER);
        $component->call('save');
        $component->assertSet('blockingDuplicate.archived', true);
        $component->assertNoRedirect();

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $contactQuery->count(),
        );
    }

    #[Test]
    public function a_business_with_the_same_identity_does_not_block_a_contact(): void
    {
        $this->createBusinessWithCompanyNumber(self::VALID_COMPANY_NUMBER);

        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Stejná firma');
        $component->set('party.registrationCountry', 'CZ');
        $component->set('party.companyNumber', self::VALID_COMPANY_NUMBER);
        $component->call('save');
        $component->assertHasNoErrors();
        $component->assertRedirect();
    }

    #[Test]
    public function the_capacity_limit_stops_a_new_contact(): void
    {
        $this->limitFrontendUser(LimitedEntity::CONTACT, 1);
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Další');
        $component->call('save');
        $component->assertSet(
            'limitMessage',
            __('contacts.messages.limit_exceeded', ['limit' => 1]),
        );

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $contactQuery->count(),
        );
    }

    #[Test]
    public function editing_clears_an_emptied_contact_person_and_email(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'contact_person' => 'Petr',
                'email' => 'petr@example.test',
            ],
        );

        $component = Livewire::test(ContactForm::class, ['contactId' => $contact->id]);
        $component->set('contactPerson', '');
        $component->set('party.email', '');
        $component->call('save');
        $component->assertHasNoErrors();

        $contact->refresh();
        $this->assertNull($contact->contact_person);
        $this->assertNull($contact->email);
    }

    #[Test]
    public function editing_needs_the_permission(): void
    {
        $userFactory = User::factory();
        $other = $userFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $other->id]);
        $this->actingAs($other);

        $component = Livewire::test(ContactForm::class, ['contactId' => $contact->id]);
        $component->assertForbidden();
    }

    private function createBusinessWithCompanyNumber(string $companyNumber): void
    {
        $businessFactory = Business::factory();
        $businessFactory->create(
            [
                'user_id' => $this->user->id,
                'company_number' => $companyNumber,
            ],
        );
    }

    #[Test]
    public function uploads_replaces_and_removes_the_contact_source_logo(): void
    {
        Storage::fake('public');
        Livewire::actingAs($this->user);
        $component = Livewire::test(ContactForm::class);
        $component->set('party.name', 'Contact with a logo');
        $fileFactory = UploadedFile::fake();
        $component->set(
            'logo',
            $fileFactory->image('logo.png'),
        );
        $component->call('save');
        $component->assertHasNoErrors();
        $contactQuery = Contact::where('user_id', $this->user->id);
        $contactQuery->where('name', 'Contact with a logo');
        $contact = $contactQuery->sole();
        $first = $contact->logo;
        $this->assertNotNull($first);
        $disk = Storage::disk('public');
        $disk->assertExists($first);
        Livewire::actingAs($this->user);
        $component = Livewire::test(ContactForm::class, ['contactId' => $contact->id]);
        $component->set(
            'logo',
            $fileFactory->image(
                'different.png',
                400,
                150,
            ),
        );
        $component->call('save');
        $component->assertHasNoErrors();
        $second = $contact->fresh()->logo;
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
        $disk->assertMissing($first);
        $disk->assertExists($second);
        Livewire::actingAs($this->user);
        $component = Livewire::test(ContactForm::class, ['contactId' => $contact->id]);
        $component->set('removeLogo', true);
        $component->call('save');
        $component->assertHasNoErrors();
        $this->assertNull($contact->fresh()->logo);
        $disk->assertMissing($second);
    }

    #[Test]
    public function cancel_leads_to_the_list_in_the_current_locale_on_a_new_contact(): void
    {
        App::setLocale('de');
        $listUrl = route('frontend.contacts.index', ['locale' => 'de']);

        $component = Livewire::test(ContactForm::class);
        $component->assertSeeHtml('href="' . $listUrl . '"');
    }

    #[Test]
    public function cancel_leads_to_the_detail_in_the_current_locale_on_an_edited_contact(): void
    {
        App::setLocale('de');
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);
        $detailUrl = route('frontend.contacts.show', ['locale' => 'de', 'contact' => $contact->id]);

        $component = Livewire::test(ContactForm::class, ['contactId' => $contact->id]);
        $component->assertSeeHtml('href="' . $detailUrl . '"');
    }
}
