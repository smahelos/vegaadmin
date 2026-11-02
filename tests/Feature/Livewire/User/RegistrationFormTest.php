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

        $component = Livewire::test(RegistrationForm::class);
        $component->set('name', 'Jan Novák');
        $component->set('email', $email);
        $component->set('password', 'secret-password');
        $component->set('passwordConfirmation', 'secret-password');
        $component->set('party.name', 'Novák Design s.r.o.');
        $component->set('party.email', 'faktury@example.test');
        $component->set('account.number', '2001234561');
        $component->set('account.bankCode', '2010');
        $component->call('save');
        $component->assertHasNoErrors();
        $component->assertRedirect();

        $userQuery = User::query();
        $userQuery->where('email', $email);
        $user = $userQuery->sole();
        $this->assertTrue(
            $user->hasRole('frontend_user'),
        );
        $this->assertAuthenticatedAs($user);
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $user->id);
        $business = $businessQuery->sole();
        $this->assertSame('Novák Design s.r.o.', $business->name);
        $this->assertSame('faktury@example.test', $business->email);
        $this->assertSame($business->id, $user->default_business_id);
        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id);
        $account = $bankAccountQuery->sole();
        $this->assertSame($account->id, $business->default_bank_account_id);
        Event::assertDispatched(Registered::class);
    }

    #[Test]
    public function the_business_name_and_email_follow_the_login_until_typed_differently(): void
    {
        $component = Livewire::test(RegistrationForm::class);
        $component->set('name', 'Jan');
        $component->assertSet('party.name', 'Jan');
        $component->set('email', 'jan@example.test');
        $component->assertSet('party.email', 'jan@example.test');
        $component->set('party.name', 'Firma Jana');
        $component->set('name', 'Jan Novák');
        $component->assertSet('party.name', 'Firma Jana');
    }

    #[Test]
    public function a_registration_with_invalid_data_creates_nothing(): void
    {
        $email = 'invalid' . uniqid() . '@example.test';

        $component = Livewire::test(RegistrationForm::class);
        $component->set('name', 'Jan');
        $component->set('email', $email);
        $component->set('password', 'secret-password');
        $component->set('passwordConfirmation', 'other-password');
        $component->set('party.name', 'Firma');
        $component->call('save');
        $component->assertHasErrors(['password']);

        $userQuery = User::query();
        $userQuery->where('email', $email);
        $this->assertSame(
            0,
            $userQuery->count(),
        );
    }

    #[Test]
    public function prefills_the_business_from_ares_without_inventing_a_vat_number(): void
    {
        Http::fake(
            [
                'ares.gov.cz/*' => Http::response(
                    [
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
                    ],
                ),
            ],
        );

        $component = Livewire::test(RegistrationForm::class);
        $component->set('party.companyNumber', '27082440');
        $component->call('lookUpCompany');
        $component->assertHasNoErrors();
        $component->assertSet('party.name', 'Alza.cz a.s.');
        $component->assertSet('party.street', 'Jankovcova 1522/53');
        $component->assertSet('party.city', 'Praha');
        $component->assertSet('party.postalCode', '17000');
        $component->assertSet('party.vatNumber', '');
        $component->assertSet(
            'registerMessage',
            __('parties.messages.register_filled'),
        );
    }

    #[Test]
    public function a_lookup_without_a_vat_number_clears_the_previous_one(): void
    {
        Http::fake(
            [
                'ares.gov.cz/*/45317054' => Http::response(
                    [
                        'obchodniJmeno' => 'Komerční banka, a.s.',
                        'dicSkDph' => 'CZ699001182',
                    ],
                ),
                'ares.gov.cz/*/27082440' => Http::response(['obchodniJmeno' => 'Bez DIČ a.s.']),
            ],
        );

        $component = Livewire::test(RegistrationForm::class);
        $component->set('party.companyNumber', '45317054');
        $component->call('lookUpCompany');
        $component->assertSet('party.vatNumber', 'CZ699001182');
        $component->set('party.companyNumber', '27082440');
        $component->call('lookUpCompany');
        $component->assertHasNoErrors();
        $component->assertSet('party.vatNumber', '');
    }
}
