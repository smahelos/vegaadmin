<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Party;

use App\Application\Party\Commands\ArchiveBankAccount;
use App\Application\Party\Commands\CreateBankAccount;
use App\Application\Party\Commands\RestoreBankAccount;
use App\Application\Party\Commands\SetDefaultBankAccount;
use App\Application\Party\Commands\UpdateBankAccount;
use App\Application\Party\Queries\ListBankAccounts;
use App\Domain\Party\DTO\BankAccountDTO;
use App\Domain\Party\DTO\BankAccountWriteData;
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
use Tests\Support\RunsUseCases;
use Tests\TestCase;

final class BankAccountUseCasesTest extends TestCase
{
    use BuildsParties;
    use RunsUseCases;

    #[Test]
    public function the_first_account_of_an_owner_becomes_its_default_and_the_next_one_does_not(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $owner = AccountOwner::contact(
            ContactId::fromInt($contact->id),
        );

        $first = $this->add(
            $this->ownerOf($contact),
            $owner,
            $this->czechAccount(),
        );
        $second = $this->add(
            $this->ownerOf($contact),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );

        $this->assertSame($first->id->value, $contact->fresh()?->default_bank_account_id);
        $this->assertCount(
            2,
            $this->listForOwner(
                $this->ownerOf($contact),
                $owner,
            ),
        );
        $this->assertNotSame($first->id->value, $second->id->value);
    }

    #[Test]
    public function archiving_the_default_account_needs_another_active_account_as_the_default(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $owner = AccountOwner::business(
            BusinessId::fromInt($business->id),
        );
        $default = $this->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );
        $other = $this->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );

        try {
            $this->archive(
                $this->ownerOf($business),
                $owner,
                $default->id,
                null,
            );
            $this->fail('The default account was archived without a replacement');
        } catch (DefaultBankAccountRequiredException) {
        }

        $this->archive(
            $this->ownerOf($business),
            $owner,
            $default->id,
            $other->id,
        );

        $this->assertSame($other->id->value, $business->fresh()?->default_bank_account_id);
        $accounts = $this->listForOwner(
            $this->ownerOf($business),
            AccountOwner::business(
                BusinessId::fromInt($business->id),
            ),
        );
        $this->assertTrue(
            $accounts[0]->isArchived(),
        );
    }

    #[Test]
    public function the_last_active_account_leaves_no_default_and_becomes_it_again_when_restored(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $owner = AccountOwner::business(
            BusinessId::fromInt($business->id),
        );
        $account = $this->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );

        $this->archive(
            $this->ownerOf($business),
            $owner,
            $account->id,
            null,
        );
        $this->assertNull($business->fresh()?->default_bank_account_id);

        $this->restore(
            $this->ownerOf($business),
            $owner,
            $account->id,
        );
        $this->assertSame($account->id->value, $business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function an_account_of_another_owner_of_the_same_user_is_no_replacement(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $sibling = $businessFactory->create(['user_id' => $business->user_id]);
        $owner = AccountOwner::business(
            BusinessId::fromInt($business->id),
        );
        $default = $this->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount(),
        );
        $this->add(
            $this->ownerOf($business),
            $owner,
            $this->czechAccount('1234567899', '0100'),
        );
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($sibling);
        $foreign = $bankAccountFactory->create();

        $this->expectException(BankAccountNotFoundException::class);

        $this->archive(
            $this->ownerOf($business),
            $owner,
            $default->id,
            BankAccountId::fromInt($foreign->id),
        );
    }

    #[Test]
    public function an_archived_account_cannot_become_the_default(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $archived = $bankAccountFactory->create(['archived_at' => now()]);

        $this->expectException(BankAccountArchivedException::class);

        $this->setDefault(
            $this->ownerOf($business),
            AccountOwner::business(
                BusinessId::fromInt($business->id),
            ),
            BankAccountId::fromInt($archived->id),
        );
    }

    #[Test]
    public function another_users_owner_and_account_are_not_found(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create();
        $stranger = $this->idOf(
            $this->frontendUser(),
        );

        try {
            $this->add(
                $stranger,
                AccountOwner::contact(
                    ContactId::fromInt($contact->id),
                ),
                $this->czechAccount(),
            );
            $this->fail('Another user added an account to the contact');
        } catch (ContactNotFoundException) {
        }

        $this->expectException(BankAccountNotFoundException::class);

        $this->setDefault(
            $stranger,
            AccountOwner::contact(
                ContactId::fromInt($contact->id),
            ),
            BankAccountId::fromInt($account->id),
        );
    }

    #[Test]
    public function updating_an_account_requires_its_selected_owner(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $business->user_id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $account = $bankAccountFactory->create();

        $this->expectException(BankAccountNotFoundException::class);

        $this->update(
            $this->ownerOf($contact),
            AccountOwner::contact(
                ContactId::fromInt($contact->id),
            ),
            BankAccountId::fromInt($account->id),
            $this->czechAccount('1234567899', '0100'),
        );
    }

    private function ownerOf(Business|Contact $party): UserId
    {
        $userQuery = User::query();
        $user = $userQuery->findOrFail($party->user_id);
        // A factory owner holds no plan role; the gate checks the owner's bank account permissions
        if (!$user->hasRole('frontend_user')) {
            $user->assignRole('frontend_user');
        }

        return $this->idOf($user);
    }

    private function add(
        UserId $user,
        AccountOwner $owner,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        return $this->runAs(
            $user,
            new CreateBankAccount(
                $user,
                $owner,
                $data,
            ),
        );
    }

    private function update(
        UserId $user,
        AccountOwner $owner,
        BankAccountId $id,
        BankAccountWriteData $data,
    ): BankAccountDTO {
        return $this->runAs(
            $user,
            new UpdateBankAccount(
                $user,
                $owner,
                $id,
                $data,
            ),
        );
    }

    private function archive(
        UserId $user,
        AccountOwner $owner,
        BankAccountId $id,
        ?BankAccountId $newDefault,
    ): void {
        $this->runAs(
            $user,
            new ArchiveBankAccount(
                $user,
                $owner,
                $id,
                $newDefault,
            ),
        );
    }

    private function restore(
        UserId $user,
        AccountOwner $owner,
        BankAccountId $id,
    ): void {
        $this->runAs(
            $user,
            new RestoreBankAccount(
                $user,
                $owner,
                $id,
            ),
        );
    }

    private function setDefault(
        UserId $user,
        AccountOwner $owner,
        BankAccountId $id,
    ): void {
        $this->runAs(
            $user,
            new SetDefaultBankAccount(
                $user,
                $owner,
                $id,
            ),
        );
    }

    /**
     * @return list<BankAccountDTO>
     */
    private function listForOwner(UserId $user, AccountOwner $owner): array
    {
        return $this->runAs(
            $user,
            new ListBankAccounts($user, $owner),
        );
    }
}
