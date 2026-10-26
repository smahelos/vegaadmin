<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party\Services;

use App\Application\Party\Services\BusinessApplicationService;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\Exceptions\BusinessArchivedException;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Exceptions\DefaultBusinessRequiredException;
use App\Domain\Party\Exceptions\DuplicateCompanyNumberException;
use App\Domain\Party\ValueObjects\Address;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BusinessApplicationServiceTest extends TestCase
{
    use BuildsParties;

    private BusinessApplicationService $businesses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businesses = $this->app->make(BusinessApplicationService::class);
    }

    #[Test]
    public function the_first_business_becomes_the_default_and_its_first_account_the_default_account(): void
    {
        $user = $this->frontendUser();

        $business = $this->businesses->create(
            $this->idOf($user),
            $this->data('Studio', $this->brnoAddress()),
            $this->czechAccount(),
        );

        $this->assertSame($business->id->value, $user->fresh()?->default_business_id);
        $account = BankAccount::query()->where('business_id', $business->id->value)->sole();
        $this->assertSame($account->id, $business->defaultBankAccountId?->value);
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame('Brno', $business->profile->address?->city);
    }

    #[Test]
    public function another_business_leaves_the_default_unchanged(): void
    {
        $user = $this->frontendUser();
        $first = $this->businesses->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );

        $second = $this->businesses->create(
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
        $first = $this->businesses->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $second = $this->businesses->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );

        try {
            $this->businesses->archive(
                $this->idOf($user),
                $first->id,
                null,
            );
            $this->fail('The default business was archived without a replacement');
        } catch (DefaultBusinessRequiredException) {
        }
        $this->assertFalse($this->businesses->get($this->idOf($user), $first->id)->isArchived());

        $this->businesses->archive(
            $this->idOf($user),
            $first->id,
            $second->id,
        );

        $this->assertTrue($this->businesses->get($this->idOf($user), $first->id)->isArchived());
        $this->assertSame($second->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function an_archived_business_is_no_replacement_for_the_default(): void
    {
        $user = $this->frontendUser();
        $first = $this->businesses->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $this->businesses->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );
        $archived = Business::factory()->archived()->create(['user_id' => $user->id]);

        $this->expectException(BusinessArchivedException::class);

        $this->businesses->archive(
            $this->idOf($user),
            $first->id,
            BusinessId::fromInt($archived->id),
        );
    }

    #[Test]
    public function the_last_active_business_can_be_archived_and_leaves_no_default(): void
    {
        $user = $this->frontendUser();
        $business = $this->businesses->create(
            $this->idOf($user),
            $this->data('Only'),
            null,
        );

        $this->businesses->archive(
            $this->idOf($user),
            $business->id,
            null,
        );

        $this->assertNull($user->fresh()?->default_business_id);
        $this->assertSame([], $this->businesses->listActive($this->idOf($user)));
    }

    #[Test]
    public function a_business_restored_while_none_is_active_becomes_the_default(): void
    {
        $user = $this->frontendUser();
        $business = $this->businesses->create(
            $this->idOf($user),
            $this->data('Only'),
            null,
        );
        $this->businesses->archive(
            $this->idOf($user),
            $business->id,
            null,
        );

        $this->businesses->restore($this->idOf($user), $business->id);

        $this->assertSame($business->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function restoring_beside_an_active_business_keeps_the_default_and_the_archived_accounts(): void
    {
        $user = $this->frontendUser();
        $default = $this->businesses->create(
            $this->idOf($user),
            $this->data('Default'),
            null,
        );
        $other = $this->businesses->create(
            $this->idOf($user),
            $this->data('Other'),
            null,
        );
        $archivedAccount = BankAccount::factory()
            ->ownedBy(Business::query()->findOrFail($other->id->value))
            ->create(['archived_at' => now()]);
        $this->businesses->archive(
            $this->idOf($user),
            $other->id,
            null,
        );

        $this->businesses->restore($this->idOf($user), $other->id);

        $this->assertSame($default->id->value, $user->fresh()?->default_business_id);
        $this->assertNotNull($archivedAccount->fresh()?->archived_at);
    }

    #[Test]
    public function restoring_needs_a_free_place_while_editing_stays_possible(): void
    {
        $this->limitActive('business', 1);
        $user = $this->frontendUser();
        $kept = $this->businesses->create(
            $this->idOf($user),
            $this->data('Kept'),
            null,
        );
        $other = Business::factory()->archived()->create(['user_id' => $user->id]);

        try {
            $this->businesses->restore($this->idOf($user), BusinessId::fromInt($other->id));
            $this->fail('The business was restored over the limit');
        } catch (EntityLimitExceededException) {
        }

        $updated = $this->businesses->update(
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
        $this->businesses->create(
            $this->idOf($user),
            $this->data('Active'),
            null,
        );
        $archived = Business::factory()->archived()->create(['user_id' => $user->id]);

        $this->expectException(BusinessArchivedException::class);

        $this->businesses->setDefault($this->idOf($user), BusinessId::fromInt($archived->id));
    }

    #[Test]
    public function the_default_can_move_to_another_active_business(): void
    {
        $user = $this->frontendUser();
        $this->businesses->create(
            $this->idOf($user),
            $this->data('First'),
            null,
        );
        $second = $this->businesses->create(
            $this->idOf($user),
            $this->data('Second'),
            null,
        );

        $this->businesses->setDefault($this->idOf($user), $second->id);

        $this->assertSame($second->id->value, $user->fresh()?->default_business_id);
    }

    #[Test]
    public function an_update_refuses_the_czech_company_number_of_another_business_of_the_user(): void
    {
        $user = $this->frontendUser();
        $holder = $this->businesses->create(
            $this->idOf($user),
            $this->data(
                'Holder',
                null,
                '27074358',
            ),
            null,
        );
        $other = $this->businesses->create(
            $this->idOf($user),
            $this->data('Other'),
            null,
        );

        try {
            $this->businesses->update(
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
            $this->assertNull(Business::query()->findOrFail($other->id->value)->company_number);

            return;
        }

        $this->fail('The duplicate was stored');
    }

    #[Test]
    public function a_business_and_a_contact_may_share_a_czech_company_number(): void
    {
        $user = $this->frontendUser();
        Contact::factory()->create([
            'user_id' => $user->id,
            'registration_country' => 'CZ',
            'company_number' => '27074358',
        ]);

        $business = $this->businesses->create(
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
        $stranger = User::factory()->create();
        $business = $this->businesses->create(
            $this->idOf($owner),
            $this->data('Secret'),
            null,
        );

        $attempts = [
            fn () => $this->businesses->get($this->idOf($stranger), $business->id),
            fn () => $this->businesses->update(
                $this->idOf($stranger),
                $business->id,
                $this->data('Taken'),
            ),
            fn () => $this->businesses->archive(
                $this->idOf($stranger),
                $business->id,
                null,
            ),
            fn () => $this->businesses->setDefault($this->idOf($stranger), $business->id),
        ];
        foreach ($attempts as $attempt) {
            try {
                $attempt();
                $this->fail('Another user reached the business');
            } catch (BusinessNotFoundException) {
            }
        }

        $this->assertNull($stranger->fresh()?->default_business_id);
        $this->assertDatabaseHas('businesses', [
            'id' => $business->id->value,
            'name' => 'Secret',
            'archived_at' => null,
        ]);
    }

    private function data(
        string $name,
        ?Address $address = null,
        ?string $companyNumber = null,
    ): BusinessWriteData {
        return new BusinessWriteData($this->profile($name, $companyNumber, address: $address), null);
    }
}
