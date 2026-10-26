<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party\Services;

use App\Application\Party\Services\ContactApplicationService;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Events\ContactCreated;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class ContactApplicationServiceTest extends TestCase
{
    use BuildsParties;

    private ContactApplicationService $contacts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contacts = $this->app->make(ContactApplicationService::class);
    }

    #[Test]
    public function a_contact_needs_only_a_name(): void
    {
        $user = $this->frontendUser();

        $contact = $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile('Jan Novák'), null));

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id->value,
            'user_id' => $user->id,
            'name' => 'Jan Novák',
            'company_number' => null,
            'registration_country' => null,
            'email' => null,
            'phone' => null,
        ]);
        $this->assertDatabaseMissing('addresses', ['contact_id' => $contact->id->value]);
    }

    #[Test]
    public function an_update_stores_emptied_fields_as_empty_and_deletes_an_emptied_address(): void
    {
        $user = $this->frontendUser();
        $contact = $this->contacts->create($this->idOf($user), new ContactWriteData(
            $this->profile(
                companyNumber: '27074358',
                email: 'office@acme.test',
                address: $this->brnoAddress(),
            ),
            'Petra',
        ));
        $this->assertSame('Brno', $contact->profile->address?->city);

        $updated = $this->contacts->update(
            $this->idOf($user),
            $contact->id,
            new ContactWriteData($this->profile(), null),
        );

        $this->assertNull($updated->profile->email);
        $this->assertNull($updated->profile->registrationNumber);
        $this->assertNull($updated->profile->address);
        $this->assertNull($updated->contactPerson);
        $this->assertDatabaseMissing('addresses', ['contact_id' => $contact->id->value]);
    }

    #[Test]
    public function a_czech_company_number_of_an_archived_contact_blocks_a_copy_and_names_the_contact(): void
    {
        $user = $this->frontendUser();
        $archived = Contact::factory()->archived()->create([
            'user_id' => $user->id,
            'registration_country' => 'CZ',
            'company_number' => '27074358',
        ]);

        try {
            $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile('Copy', '27074358'), null));
        } catch (DuplicateCompanyNumberException $e) {
            $this->assertInstanceOf(ContactId::class, $e->existing);
            $this->assertSame($archived->id, $e->existing->value);
            $this->assertSame(1, Contact::query()->where('user_id', $user->id)->count());

            return;
        }

        $this->fail('The copy was created');
    }

    #[Test]
    public function a_foreign_number_name_or_email_only_warns(): void
    {
        $user = $this->frontendUser();
        Contact::factory()->create([
            'user_id' => $user->id,
            'name' => 'Wiener GmbH',
            'registration_country' => 'AT',
            'company_number' => 'FN 123456x',
            'email' => 'office@wiener.test',
        ]);
        $profile = $this->profile(
            'Wiener GmbH',
            'FN 123456x',
            Country::AT,
            'office@wiener.test',
        );

        $duplicates = $this->contacts->findDuplicates(
            $this->idOf($user),
            $profile,
            null,
        );
        $this->contacts->create($this->idOf($user), new ContactWriteData($profile, null));

        $this->assertSame(DuplicateReason::COMPANY_NUMBER, $duplicates[0]->reason);
        $this->assertSame(2, Contact::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function the_duplicate_check_ignores_the_contact_being_edited_and_other_users(): void
    {
        $user = $this->frontendUser();
        $own = Contact::factory()->create(['user_id' => $user->id, 'name' => 'Acme']);
        Contact::factory()->create(['name' => 'Acme']);

        $duplicates = $this->contacts->findDuplicates(
            $this->idOf($user),
            $this->profile('Acme'),
            ContactId::fromInt($own->id),
        );

        $this->assertSame([], $duplicates);
    }

    #[Test]
    public function archiving_frees_a_place_and_restoring_needs_one(): void
    {
        $this->limitActive('contact', 1);
        $user = $this->frontendUser();
        $first = $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile('First'), null));

        $this->assertLimitExceeded(fn () => $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile('Second'), null)));

        $this->contacts->archive($this->idOf($user), $first->id);
        $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile('Second'), null));

        $this->assertLimitExceeded(fn () => $this->contacts->restore($this->idOf($user), $first->id));
        $this->assertTrue($this->contacts->get($this->idOf($user), $first->id)->isArchived());
    }

    #[Test]
    public function an_account_without_a_configured_limit_creates_no_contact(): void
    {
        $user = User::factory()->create();

        $this->assertLimitExceeded(fn () => $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile(), null)));
    }

    #[Test]
    public function an_archived_contact_is_listed_apart_and_still_found(): void
    {
        $user = $this->frontendUser();
        $contact = $this->contacts->create($this->idOf($user), new ContactWriteData($this->profile(), null));

        $this->contacts->archive($this->idOf($user), $contact->id);

        $this->assertSame([], $this->contacts->listActive($this->idOf($user)));
        $this->assertSame($contact->id->value, $this->contacts->listArchived($this->idOf($user))[0]->id->value);
        $this->assertTrue($this->contacts->get($this->idOf($user), $contact->id)->isArchived());
    }

    #[Test]
    public function another_users_contact_is_not_found(): void
    {
        $owner = $this->frontendUser();
        $stranger = $this->frontendUser();
        $contact = $this->contacts->create($this->idOf($owner), new ContactWriteData($this->profile('Secret'), null));

        $attempts = [
            fn () => $this->contacts->get($this->idOf($stranger), $contact->id),
            fn () => $this->contacts->update(
                $this->idOf($stranger),
                $contact->id,
                new ContactWriteData($this->profile('Taken'), null),
            ),
            fn () => $this->contacts->archive($this->idOf($stranger), $contact->id),
        ];
        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Another user reached the contact');
            } catch (ContactNotFoundException) {
            }
        }

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id->value,
            'name' => 'Secret',
            'archived_at' => null,
        ]);
        $this->assertSame([], $this->contacts->listActive($this->idOf($stranger)));
    }

    #[Test]
    public function creating_a_contact_publishes_its_event(): void
    {
        Event::fake([ContactCreated::class]);
        // Resolved after the fake, so the publisher dispatches to it
        $contacts = $this->app->make(ContactApplicationService::class);
        $user = $this->frontendUser();

        $contact = $contacts->create($this->idOf($user), new ContactWriteData($this->profile(), null));

        Event::assertDispatched(ContactCreated::class, fn (ContactCreated $event): bool => $event->contactId->equals($contact->id));
    }

    private function assertLimitExceeded(callable $operation): void
    {
        try {
            $operation();
        } catch (EntityLimitExceededException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('The limit was not enforced');
    }
}
