<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party;

use App\Application\Party\Commands\ArchiveContact;
use App\Application\Party\Commands\CreateContact;
use App\Application\Party\Commands\RestoreContact;
use App\Application\Party\Commands\UpdateContact;
use App\Application\Party\Queries\FindContactDuplicates;
use App\Application\Party\Queries\GetContact;
use App\Application\Party\Queries\ListContacts;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Party\Events\ContactCreated;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;
use App\Models\RoleLimit;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsParties;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class ContactUseCasesTest extends TestCase
{
    use BuildsParties;
    use RunsUseCases;

    #[Test]
    public function a_contact_needs_only_a_name(): void
    {
        $user = $this->frontendUser();

        $contact = $this->create(
            $this->idOf($user),
            new ContactWriteData(
                $this->profile('Jan Novák'),
                null,
            ),
        );

        $this->assertDatabaseHas(
            'contacts',
            [
                'id' => $contact->id->value,
                'user_id' => $user->id,
                'name' => 'Jan Novák',
                'company_number' => null,
                'registration_country' => null,
                'email' => null,
                'phone' => null,
            ],
        );
        $this->assertDatabaseMissing('addresses', ['contact_id' => $contact->id->value]);
    }

    #[Test]
    public function an_update_stores_emptied_fields_as_empty_and_deletes_an_emptied_address(): void
    {
        $user = $this->frontendUser();
        $contact = $this->create(
            $this->idOf($user),
            new ContactWriteData(
                $this->profile(
                    companyNumber: '27074358',
                    email: 'office@acme.test',
                    address: $this->brnoAddress(),
                ),
                'Petra',
            ),
        );
        $this->assertSame('Brno', $contact->profile->address?->city);

        $updated = $this->update(
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
        $contactFactory = Contact::factory();
        $archivedFactory = $contactFactory->archived();
        $archived = $archivedFactory->create(
            [
                'user_id' => $user->id,
                'registration_country' => 'CZ',
                'company_number' => '27074358',
            ],
        );

        try {
            $this->create(
                $this->idOf($user),
                new ContactWriteData(
                    $this->profile('Copy', '27074358'),
                    null,
                ),
            );
        } catch (DuplicateCompanyNumberException $e) {
            $this->assertInstanceOf(ContactId::class, $e->existing);
            $this->assertSame($archived->id, $e->existing->value);
            $contactQuery = Contact::query();
            $contactQuery->where('user_id', $user->id);
            $this->assertSame(
                1,
                $contactQuery->count(),
            );

            return;
        }

        $this->fail('The copy was created');
    }

    #[Test]
    public function a_foreign_number_name_or_email_only_warns(): void
    {
        $user = $this->frontendUser();
        $contactFactory = Contact::factory();
        $contactFactory->create(
            [
                'user_id' => $user->id,
                'name' => 'Wiener GmbH',
                'registration_country' => 'AT',
                'company_number' => 'FN 123456x',
                'email' => 'office@wiener.test',
            ],
        );
        $profile = $this->profile(
            'Wiener GmbH',
            'FN 123456x',
            Country::AT,
            'office@wiener.test',
        );

        $duplicates = $this->findDuplicates(
            $this->idOf($user),
            $profile,
            null,
        );
        $this->create(
            $this->idOf($user),
            new ContactWriteData($profile, null),
        );

        $this->assertSame(DuplicateReason::COMPANY_NUMBER, $duplicates[0]->reason);
        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $user->id);
        $this->assertSame(
            2,
            $contactQuery->count(),
        );
    }

    #[Test]
    public function the_duplicate_check_ignores_the_contact_being_edited_and_other_users(): void
    {
        $user = $this->frontendUser();
        $contactFactory = Contact::factory();
        $own = $contactFactory->create(['user_id' => $user->id, 'name' => 'Acme']);
        $contactFactory->create(['name' => 'Acme']);

        $duplicates = $this->findDuplicates(
            $this->idOf($user),
            $this->profile('Acme'),
            ContactId::fromInt($own->id),
        );

        $this->assertSame([], $duplicates);
    }

    #[Test]
    public function archiving_frees_a_place_and_restoring_needs_one(): void
    {
        $this->limitFrontendUser(LimitedEntity::CONTACT, 1);
        $user = $this->frontendUser();
        $first = $this->create(
            $this->idOf($user),
            new ContactWriteData(
                $this->profile('First'),
                null,
            ),
        );

        $this->assertLimitExceeded(
            fn () => $this->create(
                $this->idOf($user),
                new ContactWriteData(
                    $this->profile('Second'),
                    null,
                ),
            ),
        );

        $this->archive(
            $this->idOf($user),
            $first->id,
        );
        $this->create(
            $this->idOf($user),
            new ContactWriteData(
                $this->profile('Second'),
                null,
            ),
        );

        $this->assertLimitExceeded(
            fn () => $this->restore(
                $this->idOf($user),
                $first->id,
            ),
        );
        $contact = $this->getContact(
            $this->idOf($user),
            $first->id,
        );
        $this->assertTrue(
            $contact->isArchived(),
        );
    }

    #[Test]
    public function an_account_without_a_configured_limit_creates_no_contact(): void
    {
        // The plan role grants contact.create; without its limits row it allows no contact
        $user = $this->frontendUser();
        $role = Role::findByName('frontend_user', 'web');
        $roleLimitQuery = RoleLimit::query();
        $roleLimitQuery->where('role_id', $role->id);
        $roleLimitQuery->delete();

        $this->assertLimitExceeded(
            fn () => $this->create(
                $this->idOf($user),
                new ContactWriteData($this->profile(), null),
            ),
        );
    }

    #[Test]
    public function an_archived_contact_is_listed_apart_and_still_found(): void
    {
        $user = $this->frontendUser();
        $contact = $this->create(
            $this->idOf($user),
            new ContactWriteData($this->profile(), null),
        );

        $this->archive(
            $this->idOf($user),
            $contact->id,
        );

        $this->assertSame(
            [],
            $this->listedIds($user, false),
        );
        $this->assertSame(
            [$contact->id->value],
            $this->listedIds($user, true),
        );
        $storedContact = $this->getContact(
            $this->idOf($user),
            $contact->id,
        );
        $this->assertTrue(
            $storedContact->isArchived(),
        );
    }

    #[Test]
    public function another_users_contact_is_not_found(): void
    {
        $owner = $this->frontendUser();
        $stranger = $this->frontendUser();
        $contact = $this->create(
            $this->idOf($owner),
            new ContactWriteData(
                $this->profile('Secret'),
                null,
            ),
        );

        $attempts = [
            fn () => $this->getContact(
                $this->idOf($stranger),
                $contact->id,
            ),
            fn () => $this->update(
                $this->idOf($stranger),
                $contact->id,
                new ContactWriteData(
                    $this->profile('Taken'),
                    null,
                ),
            ),
            fn () => $this->archive(
                $this->idOf($stranger),
                $contact->id,
            ),
        ];
        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Another user reached the contact');
            } catch (ContactNotFoundException) {
            }
        }

        $this->assertDatabaseHas(
            'contacts',
            [
                'id' => $contact->id->value,
                'name' => 'Secret',
                'archived_at' => null,
            ],
        );
        $this->assertSame(
            [],
            $this->listedIds($stranger, false),
        );
    }

    #[Test]
    public function creating_a_contact_publishes_its_event(): void
    {
        Event::fake([ContactCreated::class]);
        $user = $this->frontendUser();

        $this->create(
            $this->idOf($user),
            new ContactWriteData($this->profile(), null),
        );

        Event::assertDispatched(
            ContactCreated::class,
            fn (ContactCreated $event): bool => $event->userId->toInt() === $user->id,
        );
    }

    /**
     * @return list<int>
     */
    private function listedIds(User $user, bool $archived): array
    {
        $result = $this->search(
            $this->idOf($user),
            new PartySearch(
                null,
                $archived,
                PartySort::NAME,
                true,
            ),
            new PageRequest,
        );

        return array_map(fn (ContactDTO $contact): int => $contact->id->value, $result->items());
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

    private function create(UserId $owner, ContactWriteData $data): ContactDTO
    {
        return $this->runAs(
            $owner,
            new CreateContact($owner, $data),
        );
    }

    private function update(
        UserId $owner,
        ContactId $id,
        ContactWriteData $data,
    ): ContactDTO {
        return $this->runAs(
            $owner,
            new UpdateContact(
                $owner,
                $id,
                $data,
            ),
        );
    }

    private function archive(UserId $owner, ContactId $id): void
    {
        $this->runAs(
            $owner,
            new ArchiveContact($owner, $id),
        );
    }

    private function restore(UserId $owner, ContactId $id): void
    {
        $this->runAs(
            $owner,
            new RestoreContact($owner, $id),
        );
    }

    private function getContact(UserId $owner, ContactId $id): ContactDTO
    {
        return $this->runAs(
            $owner,
            new GetContact($owner, $id),
        );
    }

    /**
     * @return PaginatedResult<ContactDTO>
     */
    private function search(
        UserId $owner,
        PartySearch $search,
        PageRequest $page,
    ): PaginatedResult {
        return $this->runAs(
            $owner,
            new ListContacts(
                $owner,
                $search,
                $page,
            ),
        );
    }

    /**
     * @return list<DuplicatePartyDTO>
     */
    private function findDuplicates(
        UserId $owner,
        PartyProfile $profile,
        ?ContactId $except,
    ): array {
        return $this->runAs(
            $owner,
            new FindContactDuplicates(
                $owner,
                $profile,
                $except,
            ),
        );
    }
}
