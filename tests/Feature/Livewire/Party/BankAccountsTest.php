<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Domain\Party\Exceptions\BankAccountNotFoundException;
use App\Livewire\Party\BankAccounts;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BankAccountsTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function the_first_account_of_a_contact_becomes_its_default(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(BankAccounts::class, ['ownerType' => 'contact', 'ownerId' => $contact->id]);
        $component->call('add');
        $component->set('account.country', 'AT');
        $component->set('account.iban', 'AT61 1904 3002 3457 3201');
        $component->call('save');
        $component->assertHasNoErrors();
        $component->assertSet('editing', false);

        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('contact_id', $contact->id);
        $account = $bankAccountQuery->sole();
        $this->assertSame('AT611904300234573201', $account->iban);
        $this->assertSame($account->id, $contact->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function refuses_an_invalid_iban(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id]);
        $component->call('add');
        $component->set('account.iban', 'CZ0000000000000000000000');
        $component->call('save');
        $component->assertHasErrors(['account.iban']);

        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id);
        $this->assertSame(
            0,
            $bankAccountQuery->count(),
        );
    }

    #[Test]
    public function a_bank_code_must_be_an_active_bank_of_the_country(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id]);
        $component->call('add');
        $component->set('account.number', '2001234561');
        $component->set('account.bankCode', '9999');
        $component->call('save');
        $component->assertHasErrors(['account.bankCode']);
    }

    #[Test]
    public function archiving_the_default_account_asks_for_a_replacement(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($business);
        $default = $ownedFactory->create();
        $other = $ownedFactory->create(
            [
                'number' => '1234567899',
                'bank_code' => '0100',
                'iban' => 'CZ9501000000001234567899',
            ],
        );
        $business->forceFill(['default_bank_account_id' => $default->id])->save();

        $accounts = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id]);
        $accounts->call('archive', $default->id);
        $accounts->assertSet('archivingId', $default->id);
        $this->assertNull($default->fresh()?->archived_at);

        $accounts->set('newDefault', (string) $other->id);
        $accounts->call('archive', $default->id);
        $this->assertNotNull($default->fresh()?->archived_at);
        $this->assertSame($other->id, $business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function another_users_account_cannot_be_edited(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $bankAccountFactory = BankAccount::factory();
        $foreign = $bankAccountFactory->create();

        $this->expectException(BankAccountNotFoundException::class);

        $component = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id]);
        $component->call('edit', $foreign->id);
    }

    #[Test]
    public function changing_accounts_needs_the_owners_update_permission(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $this->user->removeRole('frontend_user');
        $this->user->givePermissionTo('frontend.can_view_business');

        $component = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id]);
        $component->assertDontSee(
            __('parties.bank_accounts.actions.add'),
        );
        $component->call('add');
        $component->assertForbidden();
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function otherOwners(): iterable
    {
        foreach (['business', 'contact'] as $context) {
            foreach (['business', 'contact'] as $target) {
                foreach (
                    [
                        'edit',
                        'archive',
                        'restore',
                        'makeDefault',
                    ] as $action
                ) {
                    yield $context . '-' . $target . '-' . $action => [
                        $context,
                        $target,
                        $action,
                    ];
                }
            }
        }
    }

    #[Test]
    #[DataProvider('otherOwners')]
    public function account_actions_reject_another_owner_even_within_the_same_user(
        string $context,
        string $target,
        string $action,
    ): void {
        $this->user->removeRole('frontend_user');
        $this->user->givePermissionTo(
            [
                'frontend.can_view_' . $context,
                'frontend.can_update_' . $context,
            ],
        );
        $model = $context === 'business' ? Business::class : Contact::class;
        $modelFactory = $model::factory();
        $owner = $modelFactory->create(['user_id' => $this->user->id]);
        $targetModel = $target === 'business' ? Business::class : Contact::class;
        $targetModelFactory = $targetModel::factory();
        $otherOwner = $targetModelFactory->create(['user_id' => $this->user->id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($otherOwner);
        $account = $bankAccountFactory->create([
            'archived_at' => $action === 'restore' ? now() : null,
        ]);

        $component = Livewire::test(BankAccounts::class, ['ownerType' => $context, 'ownerId' => $owner->id]);
        $this->expectException(BankAccountNotFoundException::class);

        $component->call($action, $account->id);
    }
}
