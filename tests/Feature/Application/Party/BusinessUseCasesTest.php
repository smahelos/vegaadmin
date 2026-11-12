<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party;

use App\Application\Party\Commands\ArchiveBusiness;
use App\Application\Party\Commands\CreateBusiness;
use App\Application\Party\Commands\RestoreBusiness;
use App\Application\Party\Commands\SetDefaultBusiness;
use App\Application\Party\Commands\UpdateBusiness;
use App\Application\Party\Queries\GetBusiness;
use App\Application\Party\Queries\ListActiveBusinesses;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\Events\BusinessArchived;
use App\Domain\Party\Events\BusinessCreated;
use App\Domain\Party\Events\BusinessRestored;
use App\Domain\Party\Events\BusinessUpdated;
use App\Domain\Party\Events\DefaultBusinessChanged;
use App\Domain\Party\Events\PartyEvent;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Domain\User\ValueObjects\UserId;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class BusinessUseCasesTest extends TestCase
{
    use BuildsParties;
    use RunsUseCases;

    #[Test]
    public function the_first_business_becomes_the_default_and_its_first_account_the_default_account(): void
    {
        $user = $this->frontendUser();

        $business = $this->create(
            $this->idOf($user),
            $this->data('Studio', $this->brnoAddress()),
            $this->czechAccount(),
        );

        $this->assertSame($business->id->value, $user->fresh()?->default_business_id);
        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id->value);
        $account = $bankAccountQuery->sole();
        $this->assertSame($account->id, $business->defaultBankAccountId?->value);
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame('Brno', $business->profile->address?->city);
    }

    #[Test]
    public function another_business_leaves_the_default_unchanged(): void
    {
        $user = $this->frontendUser();
        $first = $this->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );

        $second = $this->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );

        $this->assertSame($first->id->value, $user->fresh()?->default_business_id);
        $this->assertNull($second->defaultBankAccountId);
    }

    #[Test]
    public function archiving_the_default_business_needs_another_active_business_as_the_default(): void
    {
        $user = $this->frontendUser();
        $first = $this->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $second = $this->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );

        try {
            $this->archive(
                $this->idOf($user),
                $first->id,
                null,
            );
            $this->fail('The default business was archived without a replacement');
        } catch (DefaultBusinessRequiredException) {
        }
        $business = $this->getBusiness(
            $this->idOf($user),
            $first->id,
        );
        $this->assertFalse(
            $business->isArchived(),
        );

        $this->archive(
            $this->idOf($user),
            $first->id,
            $second->id,
        );

        $business = $this->getBusiness(
            $this->idOf($user),
            $first->id,
        );
        $this->assertTrue(
            $business->isArchived(),
        );
        $this->assertSame($second->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function an_archived_business_is_no_replacement_for_the_default(): void
    {
        $user = $this->frontendUser();
        $first = $this->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $this->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );
        $businessFactory = Business::factory();
        $archivedFactory = $businessFactory->archived();
        $archived = $archivedFactory->create(['user_id' => $user->id]);

        $this->expectException(BusinessArchivedException::class);

        $this->archive(
            $this->idOf($user),
            $first->id,
            BusinessId::fromInt($archived->id),
        );
    }

    #[Test]
    public function the_last_active_business_can_be_archived_and_leaves_no_default(): void
    {
        $user = $this->frontendUser();
        $business = $this->create(
            $this->idOf($user),
            $this->data('Only'),
            null,
        );

        $this->archive(
            $this->idOf($user),
            $business->id,
            null,
        );

        $this->assertNull($user->fresh()?->default_business_id);
        $this->assertSame(
            [],
            $this->listActive(
                $this->idOf($user),
            ),
        );
    }

    #[Test]
    public function a_business_restored_while_none_is_active_becomes_the_default(): void
    {
        $user = $this->frontendUser();
        $business = $this->create(
            $this->idOf($user),
            $this->data('Only'),
            null,
        );
        $this->archive(
            $this->idOf($user),
            $business->id,
            null,
        );

        $this->restore(
            $this->idOf($user),
            $business->id,
        );

        $this->assertSame($business->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function restoring_beside_an_active_business_keeps_the_default_and_the_archived_accounts(): void
    {
        $user = $this->frontendUser();
        $default = $this->create(
            $this->idOf($user),
            $this->data('Default'),
            null,
        );
        $other = $this->create(
            $this->idOf($user),
            $this->data('Other'),
            null,
        );
        $bankAccountFactory = BankAccount::factory();
        $businessQuery = Business::query();
        $bankAccountFactory = $bankAccountFactory
            ->ownedBy(
                $businessQuery->findOrFail($other->id->value),
            );
        $archivedAccount = $bankAccountFactory
            ->create(['archived_at' => now()]);
        $this->archive(
            $this->idOf($user),
            $other->id,
            null,
        );

        $this->restore(
            $this->idOf($user),
            $other->id,
        );

        $this->assertSame($default->id->value, $user->fresh()?->default_business_id);
        $this->assertNotNull($archivedAccount->fresh()?->archived_at);
    }

    #[Test]
    public function restoring_needs_a_free_place_while_editing_stays_possible(): void
    {
        $this->limitFrontendUser(LimitedEntity::BUSINESS, 1);
        $user = $this->frontendUser();
        $kept = $this->create(
            $this->idOf($user),
            $this->data('Kept'),
            null,
        );
        $businessFactory = Business::factory();
        $archivedFactory = $businessFactory->archived();
        $other = $archivedFactory->create(['user_id' => $user->id]);

        try {
            $this->restore(
                $this->idOf($user),
                BusinessId::fromInt($other->id),
            );
            $this->fail('The business was restored over the limit');
        } catch (EntityLimitExceededException) {
        }

        $updated = $this->update(
            $this->idOf($user),
            $kept->id,
            $this->data('Kept and edited'),
        );
        $this->assertSame('Kept and edited', $updated->profile->name);
        $this->assertNotNull($other->fresh()?->archived_at);
    }

    #[Test]
    public function an_archived_business_cannot_become_the_default(): void
    {
        $user = $this->frontendUser();
        $this->create(
            $this->idOf($user),
            $this->data('Active'),
            null,
        );
        $businessFactory = Business::factory();
        $archivedFactory = $businessFactory->archived();
        $archived = $archivedFactory->create(['user_id' => $user->id]);

        $this->expectException(BusinessArchivedException::class);

        $this->setDefault(
            $this->idOf($user),
            BusinessId::fromInt($archived->id),
        );
    }

    #[Test]
    public function the_default_can_move_to_another_active_business(): void
    {
        $user = $this->frontendUser();
        $this->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $second = $this->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );

        $this->setDefault(
            $this->idOf($user),
            $second->id,
        );

        $this->assertSame($second->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function an_update_refuses_the_czech_company_number_of_another_business_of_the_user(): void
    {
        $user = $this->frontendUser();
        $holder = $this->create(
            $this->idOf($user),
            $this->data(
                'Holder',
                null,
                '27074358',
            ),
            null,
        );
        $other = $this->create(
            $this->idOf($user),
            $this->data('Other'),
            null,
        );

        try {
            $this->update(
                $this->idOf($user),
                $other->id,
                $this->data(
                    'Other',
                    null,
                    '27074358',
                ),
            );
        } catch (DuplicateCompanyNumberException $e) {
            $this->assertSame($holder->id->value, $e->existing->value);
            $businessQuery = Business::query();
            $this->assertNull(
                $businessQuery->findOrFail($other->id->value)->company_number,
            );

            return;
        }

        $this->fail('The duplicate was stored');
    }

    #[Test]
    public function a_business_and_a_contact_may_share_a_czech_company_number(): void
    {
        $user = $this->frontendUser();
        $contactFactory = Contact::factory();
        $contactFactory->create(
            [
                'user_id' => $user->id,
                'registration_country' => 'CZ',
                'company_number' => '27074358',
            ],
        );

        $business = $this->create(
            $this->idOf($user),
            $this->data(
                'Own',
                null,
                '27074358',
            ),
            null,
        );

        $this->assertSame('27074358', $business->profile->registrationNumber?->number);
    }

    #[Test]
    public function another_users_business_is_not_found(): void
    {
        $owner = $this->frontendUser();
        $stranger = $this->frontendUser();
        $business = $this->create(
            $this->idOf($owner),
            $this->data('Secret'),
            null,
        );

        $attempts = [
            fn () => $this->getBusiness(
                $this->idOf($stranger),
                $business->id,
            ),
            fn () => $this->update(
                $this->idOf($stranger),
                $business->id,
                $this->data('Taken'),
            ),
            fn () => $this->archive(
                $this->idOf($stranger),
                $business->id,
                null,
            ),
            fn () => $this->setDefault(
                $this->idOf($stranger),
                $business->id,
            ),
        ];
        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Another user reached the business');
            } catch (BusinessNotFoundException) {
            }
        }

        $this->assertNull($stranger->fresh()?->default_business_id);
        $this->assertDatabaseHas(
            'businesses',
            [
                'id' => $business->id->value,
                'name' => 'Secret',
                'archived_at' => null,
            ],
        );
    }

    #[Test]
    public function every_change_of_a_business_publishes_its_event_for_the_owner(): void
    {
        Event::fake(
            [
                BusinessArchived::class,
                BusinessCreated::class,
                BusinessRestored::class,
                BusinessUpdated::class,
                DefaultBusinessChanged::class,
            ],
        );
        $user = $this->frontendUser();

        $business = $this->create(
            $this->idOf($user),
            $this->data('Only'),
            null,
        );
        $this->update(
            $this->idOf($user),
            $business->id,
            $this->data('Renamed'),
        );
        $this->archive(
            $this->idOf($user),
            $business->id,
            null,
        );
        $this->restore(
            $this->idOf($user),
            $business->id,
        );

        foreach (
            [
                BusinessCreated::class,
                BusinessUpdated::class,
                BusinessArchived::class,
                BusinessRestored::class,
            ] as $event
        ) {
            Event::assertDispatched(
                $event,
                fn (PartyEvent $published): bool => $published->userId->toInt() === $user->id,
            );
        }
        // Set on creation, dropped with the last active business, set again on its restore
        Event::assertDispatchedTimes(DefaultBusinessChanged::class, 3);
    }

    private function data(
        string $name,
        ?Address $address = null,
        ?string $companyNumber = null,
    ): BusinessWriteData {
        return new BusinessWriteData(
            $this->profile(
                $name,
                $companyNumber,
                address: $address,
            ),
            null,
        );
    }

    private function create(
        UserId $owner,
        BusinessWriteData $data,
        ?BankAccountWriteData $firstAccount,
    ): BusinessDTO {
        return $this->runAs(
            $owner,
            new CreateBusiness(
                $owner,
                $data,
                $firstAccount,
            ),
        );
    }

    private function update(
        UserId $owner,
        BusinessId $id,
        BusinessWriteData $data,
    ): BusinessDTO {
        return $this->runAs(
            $owner,
            new UpdateBusiness(
                $owner,
                $id,
                $data,
            ),
        );
    }

    private function archive(
        UserId $owner,
        BusinessId $id,
        ?BusinessId $newDefault,
    ): void {
        $this->runAs(
            $owner,
            new ArchiveBusiness(
                $owner,
                $id,
                $newDefault,
            ),
        );
    }

    private function restore(UserId $owner, BusinessId $id): void
    {
        $this->runAs(
            $owner,
            new RestoreBusiness($owner, $id),
        );
    }

    private function setDefault(UserId $owner, BusinessId $id): void
    {
        $this->runAs(
            $owner,
            new SetDefaultBusiness($owner, $id),
        );
    }

    private function getBusiness(UserId $owner, BusinessId $id): BusinessDTO
    {
        return $this->runAs(
            $owner,
            new GetBusiness($owner, $id),
        );
    }

    /**
     * @return list<BusinessDTO>
     */
    private function listActive(UserId $owner): array
    {
        return $this->runAs(
            $owner,
            new ListActiveBusinesses($owner),
        );
    }
}
