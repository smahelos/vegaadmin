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
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(BankAccounts::class, ['ownerType' => 'contact', 'ownerId' => $contact->id])
            ->call('add')
            ->set('account.country', 'AT')
            ->set('account.iban', 'AT61 1904 3002 3457 3201')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('editing', false);

        $account = BankAccount::query()->where('contact_id', $contact->id)->sole();
        $this->assertSame('AT611904300234573201', $account->iban);
        $this->assertSame($account->id, $contact->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function refuses_an_invalid_iban(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id])
            ->call('add')
            ->set('account.iban', 'CZ0000000000000000000000')
            ->call('save')
            ->assertHasErrors(['account.iban']);

        $this->assertSame(0, BankAccount::query()->where('business_id', $business->id)->count());
    }

    #[Test]
    public function a_bank_code_must_be_an_active_bank_of_the_country(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id])
            ->call('add')
            ->set('account.number', '2001234561')
            ->set('account.bankCode', '9999')
            ->call('save')
            ->assertHasErrors(['account.bankCode']);
    }

    #[Test]
    public function archiving_the_default_account_asks_for_a_replacement(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $default = BankAccount::factory()->ownedBy($business)->create();
        $other = BankAccount::factory()->ownedBy($business)->create([
            'number' => '1234567899',
            'bank_code' => '0100',
            'iban' => 'CZ9501000000001234567899',
        ]);
        $business->forceFill(['default_bank_account_id' => $default->id])->save();

        $accounts = Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id])
            ->call('archive', $default->id)
            ->assertSet('archivingId', $default->id);
        $this->assertNull($default->fresh()?->archived_at);

        $accounts->set('newDefault', (string) $other->id)->call('archive', $default->id);
        $this->assertNotNull($default->fresh()?->archived_at);
        $this->assertSame($other->id, $business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function another_users_account_cannot_be_edited(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $foreign = BankAccount::factory()->create();

        $this->expectException(BankAccountNotFoundException::class);

        Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id])
            ->call('edit', $foreign->id);
    }

    #[Test]
    public function changing_accounts_needs_the_owners_update_permission(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->user->removeRole('frontend_user');
        $this->user->givePermissionTo('frontend.can_view_business');

        Livewire::test(BankAccounts::class, ['ownerType' => 'business', 'ownerId' => $business->id])
            ->assertDontSee(__('parties.bank_accounts.actions.add'))
            ->call('add')
            ->assertForbidden();
    }
}
