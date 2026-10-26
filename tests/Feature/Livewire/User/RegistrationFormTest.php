<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\RegistrationForm;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RegistrationFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
    }

    #[Test]
    public function registers_the_user_with_a_default_business_and_its_account(): void
    {
        Event::fake([Registered::class]);
        $email = 'novak' . uniqid() . '@example.test';

        Livewire::test(RegistrationForm::class)
            ->set('name', 'Jan Novák')
            ->set('email', $email)
            ->set('password', 'secret-password')
            ->set('passwordConfirmation', 'secret-password')
            ->set('party.name', 'Novák Design s.r.o.')
            ->set('party.email', 'faktury@example.test')
            ->set('account.number', '2001234561')
            ->set('account.bankCode', '2010')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::query()->where('email', $email)->sole();
        $this->assertTrue($user->hasRole('frontend_user'));
        $this->assertAuthenticatedAs($user);
        $business = Business::query()->where('user_id', $user->id)->sole();
        $this->assertSame('Novák Design s.r.o.', $business->name);
        $this->assertSame('faktury@example.test', $business->email);
        $this->assertSame($business->id, $user->default_business_id);
        $account = BankAccount::query()->where('business_id', $business->id)->sole();
        $this->assertSame($account->id, $business->default_bank_account_id);
        Event::assertDispatched(Registered::class);
    }

    #[Test]
    public function the_business_name_and_email_follow_the_login_until_typed_differently(): void
    {
        Livewire::test(RegistrationForm::class)
            ->set('name', 'Jan')
            ->assertSet('party.name', 'Jan')
            ->set('email', 'jan@example.test')
            ->assertSet('party.email', 'jan@example.test')
            ->set('party.name', 'Firma Jana')
            ->set('name', 'Jan Novák')
            ->assertSet('party.name', 'Firma Jana');
    }

    #[Test]
    public function a_registration_with_invalid_data_creates_nothing(): void
    {
        $email = 'invalid' . uniqid() . '@example.test';

        Livewire::test(RegistrationForm::class)
            ->set('name', 'Jan')
            ->set('email', $email)
            ->set('password', 'secret-password')
            ->set('passwordConfirmation', 'other-password')
            ->set('party.name', 'Firma')
            ->call('save')
            ->assertHasErrors(['password']);

        $this->assertSame(0, User::query()->where('email', $email)->count());
    }

    #[Test]
    public function prefills_the_business_from_ares_without_inventing_a_vat_number(): void
    {
        Http::fake([
            'ares.gov.cz/*' => Http::response([
                'ico' => '27082440',
                'obchodniJmeno' => 'Alza.cz a.s.',
                'sidlo' => [
                    'kodStatu' => 'CZ',
                    'nazevObce' => 'Praha',
                    'nazevUlice' => 'Jankovcova',
                    'cisloDomovni' => 1522,
                    'cisloOrientacni' => 53,
                    'psc' => 17000,
                ],
            ]),
        ]);

        Livewire::test(RegistrationForm::class)
            ->set('party.companyNumber', '27082440')
            ->call('lookUpCompany')
            ->assertHasNoErrors()
            ->assertSet('party.name', 'Alza.cz a.s.')
            ->assertSet('party.street', 'Jankovcova 1522/53')
            ->assertSet('party.city', 'Praha')
            ->assertSet('party.postalCode', '17000')
            ->assertSet('party.vatNumber', '')
            ->assertSet('registerMessage', __('parties.messages.register_filled'));
    }
}
