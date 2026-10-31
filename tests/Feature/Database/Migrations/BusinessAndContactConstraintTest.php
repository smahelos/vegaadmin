<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Database\Factories\BusinessFactory;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AssertsDatabaseRefusal;
use Tests\TestCase;

/**
 * The database keeps the address book consistent even when two requests race past the application's checks:
 * a Czech company number once per account, every address and account with exactly one owner, and default
 * selections only within the owner.
 */
final class BusinessAndContactConstraintTest extends TestCase
{
    use AssertsDatabaseRefusal;

    /**
     * @return iterable<string, array{class-string<Business|Contact>}>
     */
    public static function parties(): iterable
    {
        yield 'business' => [Business::class];
        yield 'contact' => [Contact::class];
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('parties')]
    public function a_czech_company_number_is_refused_twice_in_one_account_archived_records_included(string $model): void
    {
        $archived = $this->factoryOf($model)->archived()->create();

        $this->assertRefused(
            fn () => $model::factory()->create([
                'user_id' => $archived->user_id,
                'company_number' => $archived->company_number,
            ]),
            1062,
        );
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('parties')]
    public function the_same_czech_company_number_is_stored_in_another_account(string $model): void
    {
        $party = $model::factory()->create();

        $copy = $model::factory()->create(['company_number' => $party->company_number]);

        $this->assertDatabaseHas($party->getTable(), ['id' => $copy->id, 'company_number' => $party->company_number]);
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('parties')]
    public function foreign_and_missing_company_numbers_may_repeat_in_one_account(string $model): void
    {
        $user = User::factory()->create();

        foreach ([
            ['registration_country' => 'AT', 'company_number' => 'FN 123456a'],
            ['registration_country' => null, 'company_number' => null],
        ] as $identity) {
            $model::factory()->count(2)->create(['user_id' => $user->id, ...$identity]);
        }

        $this->assertSame(4, $model::where('user_id', $user->id)->count());
    }

    /**
     * @return iterable<string, array{class-string<Address|BankAccount>}>
     */
    public static function ownedRecords(): iterable
    {
        yield 'address' => [Address::class];
        yield 'bank account' => [BankAccount::class];
    }

    /**
     * @param  class-string<Address|BankAccount>  $model
     */
    #[Test]
    #[DataProvider('ownedRecords')]
    public function an_address_or_account_needs_exactly_one_owner(string $model): void
    {
        $business = Business::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $business->user_id]);

        $this->assertRefused(fn () => $model::factory()->create(['business_id' => null, 'contact_id' => null]), 3819);
        $this->assertRefused(fn () => $model::factory()->create(['business_id' => $business->id, 'contact_id' => $contact->id]), 3819);
    }

    #[Test]
    public function a_default_account_belongs_to_its_owner(): void
    {
        $business = Business::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $business->user_id]);
        $otherBusiness = Business::factory()->create(['user_id' => $business->user_id]);
        $ownAccount = BankAccount::factory()->ownedBy($business)->create();
        $contactAccount = BankAccount::factory()->ownedBy($contact)->create();

        $business->forceFill(['default_bank_account_id' => $ownAccount->id])->save();
        $contact->forceFill(['default_bank_account_id' => $contactAccount->id])->save();

        $this->assertDefaultRefused($otherBusiness, $ownAccount->id);
        $this->assertDefaultRefused($business, $contactAccount->id);
        $this->assertDefaultRefused($contact, $ownAccount->id);
        $this->assertDatabaseHas('businesses', ['id' => $business->id, 'default_bank_account_id' => $ownAccount->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'default_bank_account_id' => $contactAccount->id]);
    }

    #[Test]
    public function the_default_business_belongs_to_the_user(): void
    {
        $business = Business::factory()->create();
        $user = User::findOrFail($business->user_id);
        $otherUsersBusiness = Business::factory()->create();

        $user->forceFill(['default_business_id' => $business->id])->save();

        $this->assertRefused(fn () => $user->forceFill(['default_business_id' => $otherUsersBusiness->id])->save(), 1452);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'default_business_id' => $business->id]);
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    private function factoryOf(string $model): BusinessFactory|ContactFactory
    {
        return $model === Business::class ? Business::factory() : Contact::factory();
    }

    private function assertDefaultRefused(Model $owner, int $accountId): void
    {
        $this->assertRefused(fn () => $owner->forceFill(['default_bank_account_id' => $accountId])->save(), 1452);
        $owner->refresh();
    }
}
