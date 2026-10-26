<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\ContactForm;
use App\Models\Address;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
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
        Livewire::test(ContactForm::class)
            ->set('party.name', 'Jan Novák')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $contact = Contact::query()->where('user_id', $this->user->id)->sole();
        $this->assertNull($contact->email);
        $this->assertNull($contact->phone);
        $this->assertSame(0, Address::query()->where('contact_id', $contact->id)->count());
    }

    #[Test]
    public function stores_a_foreign_company_with_its_own_registration_country(): void
    {
        Livewire::test(ContactForm::class)
            ->set('party.name', 'Wiener GmbH')
            ->set('party.country', 'AT')
            ->set('party.companyNumber', 'FN 123456a')
            ->set('party.street', 'Ring 1')
            ->set('party.city', 'Wien')
            ->set('party.postalCode', '1010')
            ->set('contactPerson', 'Anna Huber')
            ->call('save')
            ->assertHasNoErrors();

        $contact = Contact::query()->where('user_id', $this->user->id)->sole();
        $this->assertSame('AT', $contact->registration_country);
        $this->assertSame('FN 123456a', $contact->company_number);
        $this->assertSame('Anna Huber', $contact->contact_person);
        $this->assertSame('AT', Address::query()->where('contact_id', $contact->id)->value('country'));
    }

    #[Test]
    public function an_archived_contact_with_the_same_czech_company_number_blocks_a_copy(): void
    {
        Contact::factory()->archived()->create([
            'user_id' => $this->user->id,
            'company_number' => self::VALID_COMPANY_NUMBER,
        ]);

        Livewire::test(ContactForm::class)
            ->set('party.name', 'Kopie')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', self::VALID_COMPANY_NUMBER)
            ->call('save')
            ->assertSet('blockingDuplicate.archived', true)
            ->assertNoRedirect();

        $this->assertSame(1, Contact::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function a_business_with_the_same_identity_does_not_block_a_contact(): void
    {
        $this->createBusinessWithCompanyNumber(self::VALID_COMPANY_NUMBER);

        Livewire::test(ContactForm::class)
            ->set('party.name', 'Stejná firma')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', self::VALID_COMPANY_NUMBER)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();
    }

    #[Test]
    public function the_capacity_limit_stops_a_new_contact(): void
    {
        $this->limitActive('contact', 1);
        Contact::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(ContactForm::class)
            ->set('party.name', 'Další')
            ->call('save')
            ->assertSet('limitMessage', __('contacts.messages.limit_exceeded'));

        $this->assertSame(1, Contact::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function editing_clears_an_emptied_contact_person_and_email(): void
    {
        $contact = Contact::factory()->create([
            'user_id' => $this->user->id,
            'contact_person' => 'Petr',
            'email' => 'petr@example.test',
        ]);

        Livewire::test(ContactForm::class, ['contactId' => $contact->id])
            ->set('contactPerson', '')
            ->set('party.email', '')
            ->call('save')
            ->assertHasNoErrors();

        $contact->refresh();
        $this->assertNull($contact->contact_person);
        $this->assertNull($contact->email);
    }

    #[Test]
    public function editing_needs_the_permission(): void
    {
        $other = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $other->id]);
        $this->actingAs($other);

        Livewire::test(ContactForm::class, ['contactId' => $contact->id])->assertForbidden();
    }

    private function createBusinessWithCompanyNumber(string $companyNumber): void
    {
        Business::factory()->create([
            'user_id' => $this->user->id,
            'company_number' => $companyNumber,
        ]);
    }
}
