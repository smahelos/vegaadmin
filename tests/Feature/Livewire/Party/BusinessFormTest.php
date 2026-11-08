<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Domain\User\Enums\LimitedEntity;
use App\Livewire\Party\BusinessForm;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class BusinessFormTest extends TestCase
{
    use BuildsParties;

    private const VALID_COMPANY_NUMBER = '27082440';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function creates_a_business_with_its_address_and_first_account_as_the_default(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Acme s.r.o.');
        $component->set('party.registrationCountry', 'CZ');
        $component->set('party.companyNumber', self::VALID_COMPANY_NUMBER);
        $component->set('party.street', 'Hlavní 1');
        $component->set('party.city', 'Brno');
        $component->set('party.postalCode', '602 00');
        $component->set('party.country', 'CZ');
        $component->set('account.number', '2001234561');
        $component->set('account.bankCode', '2010');
        $component->call('save');
        $component->assertHasNoErrors();
        $component->assertRedirect();

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $business = $businessQuery->sole();
        $this->assertSame(self::VALID_COMPANY_NUMBER, $business->company_number);
        $addressQuery = Address::query();
        $addressQuery->where('business_id', $business->id);
        $this->assertSame(
            'Brno',
            $addressQuery->value('city'),
        );
        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id);
        $account = $bankAccountQuery->sole();
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame($account->id, $business->fresh()?->default_bank_account_id);
        $this->assertSame($business->id, $this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function a_business_needs_only_its_name(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Jen název');
        $component->call('save');
        $component->assertHasNoErrors();

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $business = $businessQuery->sole();
        $this->assertNull($business->company_number);
        $addressQuery = Address::query();
        $addressQuery->where('business_id', $business->id);
        $this->assertSame(
            0,
            $addressQuery->count(),
        );
        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('business_id', $business->id);
        $this->assertSame(
            0,
            $bankAccountQuery->count(),
        );
    }

    #[Test]
    public function refuses_a_czech_company_number_with_a_wrong_checksum(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Acme');
        $component->set('party.registrationCountry', 'CZ');
        $component->set('party.companyNumber', '27082441');
        $component->call('save');
        $component->assertHasErrors(['party.companyNumber']);

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            0,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function a_company_number_needs_its_registration_country(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Acme');
        $component->set('party.country', '');
        $component->set('party.companyNumber', 'HRB 12345');
        $component->call('save');
        $component->assertHasErrors(['party.registrationCountry']);
    }

    #[Test]
    public function the_registration_country_follows_the_address_while_it_is_empty(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.country', 'AT');
        $component->set('party.companyNumber', 'FN 12345a');
        $component->assertSet('party.registrationCountry', 'AT');
        $component->set('party.country', 'DE');
        $component->assertSet('party.registrationCountry', 'AT');
    }

    #[Test]
    public function an_address_part_needs_the_country(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Acme');
        $component->set('party.city', 'Brno');
        $component->set('party.country', '');
        $component->call('save');
        $component->assertHasErrors(['party.country']);
    }

    #[Test]
    public function refuses_an_incomplete_first_account(): void
    {
        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Acme');
        $component->set('account.number', '2001234561');
        $component->call('save');
        $component->assertHasErrors(['account.number']);

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            0,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function a_czech_company_number_of_another_business_blocks_the_save_and_points_to_it(): void
    {
        $businessFactory = Business::factory();
        $archivedFactory = $businessFactory->archived();
        $existing = $archivedFactory->create(
            [
                'user_id' => $this->user->id,
                'company_number' => self::VALID_COMPANY_NUMBER,
            ],
        );

        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Kopie');
        $component->set('party.registrationCountry', 'CZ');
        $component->set('party.companyNumber', self::VALID_COMPANY_NUMBER);
        $component->call('save');
        $component->assertSet('blockingDuplicate.archived', true);
        $component->assertSet(
            'blockingDuplicate.url',
            route('frontend.businesses.show', ['business' => $existing->id]),
        );
        $component->assertNoRedirect();

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function a_matching_name_only_warns_and_saves_once_confirmed(): void
    {
        $businessFactory = Business::factory();
        $businessFactory->create([
            'user_id' => $this->user->id,
            'name' => 'Acme',
        ]);

        $form = Livewire::test(BusinessForm::class);
        $form->set('party.name', 'Acme');
        $form->call('save');
        $form->assertCount('duplicateWarnings', 1);
        $form->assertNoRedirect();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $businessQuery->count(),
        );

        $form->call('confirmDuplicates');
        $form->assertRedirect();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            2,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function a_new_business_shares_the_creation_rate_limit_with_the_other_creation_paths(): void
    {
        Config::set('billex.rate_limits.create', 1);
        $this->freezeTime();
        $this->post('/cs/product');

        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Příliš rychle');
        $component->call('save');

        $component->assertSet(
            'errorMessage',
            __('general.messages.too_many_requests', ['seconds' => 60]),
        );
        $component->assertNoRedirect();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(0, $businessQuery->count());
    }

    #[Test]
    public function the_capacity_limit_stops_a_new_business(): void
    {
        $this->limitFrontendUser(LimitedEntity::BUSINESS, 1);
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $this->user->id]);

        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Druhá');
        $component->call('save');
        $component->assertSet(
            'limitMessage',
            __('businesses.messages.limit_exceeded', ['limit' => 1]),
        );
        $component->assertSee(
            __('subscriptions.actions.view_plans'),
        );
        $component->assertNoRedirect();

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function emptied_optional_fields_are_stored_empty(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(
            [
                'user_id' => $this->user->id,
                'email' => 'old@example.test',
                'phone' => '+420 123',
            ],
        );
        $addressFactory = Address::factory();
        $addressFactory->create(['business_id' => $business->id]);

        $component = Livewire::test(BusinessForm::class, ['businessId' => $business->id]);
        $component->assertSet('party.email', 'old@example.test');
        $component->set('party.email', '');
        $component->set('party.phone', '');
        $component->set('party.street', '');
        $component->set('party.city', '');
        $component->set('party.postalCode', '');
        $component->call('save');
        $component->assertHasNoErrors();

        $business->refresh();
        $this->assertNull($business->email);
        $this->assertNull($business->phone);
        $addressQuery = Address::query();
        $addressQuery->where('business_id', $business->id);
        $this->assertSame(
            0,
            $addressQuery->count(),
        );
    }

    #[Test]
    public function stores_the_logo_and_replaces_it_on_edit(): void
    {
        Storage::fake('public');

        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'S logem');
        $fileFactory = UploadedFile::fake();
        $component->set(
            'logo',
            $fileFactory->image(
                'logo.png',
                200,
                100,
            ),
        );
        $component->call('save');
        $component->assertHasNoErrors();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $business = $businessQuery->sole();
        $first = $business->logo;
        $this->assertNotNull($first);
        $disk = Storage::disk('public');
        $disk->assertExists($first);

        $component = Livewire::test(BusinessForm::class, ['businessId' => $business->id]);
        $component->set(
            'logo',
            $fileFactory->image(
                'new.png',
                300,
                120,
            ),
        );
        $component->call('save');
        $component->assertHasNoErrors();
        $second = $business->fresh()?->logo;
        $this->assertNotSame($first, $second);
        $disk->assertMissing($first);
    }

    #[Test]
    public function refuses_a_logo_that_is_not_an_image(): void
    {
        Storage::fake('public');

        $component = Livewire::test(BusinessForm::class);
        $component->set('party.name', 'Bez loga');
        $fileFactory = UploadedFile::fake();
        $component->set(
            'logo',
            $fileFactory->create(
                'logo.pdf',
                10,
                'application/pdf',
            ),
        );
        $component->call('save');
        $component->assertHasErrors(['logo']);

        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            0,
            $businessQuery->count(),
        );
    }

    #[Test]
    public function creating_needs_the_permission(): void
    {
        $userFactory = User::factory();
        $this->actingAs($userFactory->create());

        $component = Livewire::test(BusinessForm::class);
        $component->assertForbidden();
    }

    #[Test]
    public function cancel_leads_to_the_list_in_the_current_locale_on_a_new_business(): void
    {
        App::setLocale('de');
        $listUrl = route('frontend.businesses.index', ['locale' => 'de']);

        $component = Livewire::test(BusinessForm::class);
        $component->assertSeeHtml('href="' . $listUrl . '"');
    }

    #[Test]
    public function cancel_leads_to_the_detail_in_the_current_locale_on_an_edited_business(): void
    {
        App::setLocale('de');
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $detailUrl = route('frontend.businesses.show', ['locale' => 'de', 'business' => $business->id]);

        $component = Livewire::test(BusinessForm::class, ['businessId' => $business->id]);
        $component->assertSeeHtml('href="' . $detailUrl . '"');
    }
}
