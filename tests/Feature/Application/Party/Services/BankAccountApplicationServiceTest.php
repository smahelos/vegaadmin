<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party\Services;

use App\Application\Party\Services\BankAccountApplicationService;
use App\Domain\Party\Exceptions\BankAccountArchivedException;
use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Domain\Party\Exceptions\ContactNotFoundException;
use App\Domain\Party\Exceptions\DefaultBankAccountRequiredException;
use App\Domain\Party\ValueObjects\AccountOwner;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BankAccountApplicationServiceTest extends TestCase
{
    use BuildsParties;

    private BankAccountApplicationService $accounts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accounts = $this->app->make(BankAccountApplicationService::class);
    }

    #[Test]
    public function the_first_account_of_an_owner_becomes_its_default_and_the_next_one_does_not(): void
    {
        $contact = Contact::factory()->create();
        $owner = AccountOwner::contact(ContactId::fromInt($contact->id));

        $first = $this->accounts->add(
            $this->ownerOf($contact),
            $owner,
            $this->czechAccount(),
        );
        $second = $this->accounts->add(
            $this->ownerOf($contact),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );

        $this->assertSame($first->id->value, $contact->fresh()?->default_bank_account_id);
        $this->assertCount(2, $this->accounts->listForOwner($this->ownerOf($contact), $owner));
        $this->assertNotSame($first->id->value, $second->id->value);
    }

    #[Test]
    public function archiving_the_default_account_needs_another_active_account_as_the_default(): void
    {
        $business = Business::factory()->create();
        $owner = AccountOwner::business(BusinessId::fromInt($business->id));
        $default = $this->accounts->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );
        $other = $this->accounts->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );

        try {
            $this->accounts->archive(
                $this->ownerOf($business),
                $default->id,
                null,
            );
            $this->fail('The default account was archived without a replacement');
        } catch (DefaultBankAccountRequiredException) {
        }

        $this->accounts->archive(
            $this->ownerOf($business),
            $default->id,
            $other->id,
        );

        $this->assertSame($other->id->value, $business->fresh()?->default_bank_account_id);
        $this->assertTrue($this->accounts->get($this->ownerOf($business), $default->id)->isArchived());
    }

    #[Test]
    public function the_last_active_account_leaves_no_default_and_becomes_it_again_when_restored(): void
    {
        $business = Business::factory()->create();
        $owner = AccountOwner::business(BusinessId::fromInt($business->id));
        $account = $this->accounts->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );

        $this->accounts->archive(
            $this->ownerOf($business),
            $account->id,
            null,
        );
        $this->assertNull($business->fresh()?->default_bank_account_id);

        $this->accounts->restore($this->ownerOf($business), $account->id);
        $this->assertSame($account->id->value, $business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function an_account_of_another_owner_of_the_same_user_is_no_replacement(): void
    {
        $business = Business::factory()->create();
        $sibling = Business::factory()->create(['user_id' => $business->user_id]);
        $owner = AccountOwner::business(BusinessId::fromInt($business->id));
        $default = $this->accounts->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );
        $this->accounts->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );
        $foreign = BankAccount::factory()->ownedBy($sibling)->create();

        $this->expectException(BankAccountNotFoundException::class);

        $this->accounts->archive(
            $this->ownerOf($business),
            $default->id,
            BankAccountId::fromInt($foreign->id),
        );
    }

    #[Test]
    public function an_archived_account_cannot_become_the_default(): void
    {
        $business = Business::factory()->create();
        $archived = BankAccount::factory()->ownedBy($business)->create(['archived_at' => now()]);

        $this->expectException(BankAccountArchivedException::class);

        $this->accounts->setDefault($this->ownerOf($business), BankAccountId::fromInt($archived->id));
    }

    #[Test]
    public function another_users_owner_and_account_are_not_found(): void
    {
        $contact = Contact::factory()->create();
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $stranger = $this->idOf(User::factory()->create());

        try {
            $this->accounts->add(
                $stranger,
                AccountOwner::contact(ContactId::fromInt($contact->id)),
                $this->czechAccount(),
            );
            $this->fail('Another user added an account to the contact');
        } catch (ContactNotFoundException) {
        }

        $this->expectException(BankAccountNotFoundException::class);

        $this->accounts->setDefault($stranger, BankAccountId::fromInt($account->id));
    }

    private function ownerOf(Business|Contact $party): UserId
    {
        return $this->idOf(User::query()->findOrFail($party->user_id));
    }
}
