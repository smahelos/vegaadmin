<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\BusinessForm;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme s.r.o.')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', self::VALID_COMPANY_NUMBER)
            ->set('party.street', 'Hlavní 1')
            ->set('party.city', 'Brno')
            ->set('party.postalCode', '602 00')
            ->set('party.country', 'CZ')
            ->set('account.number', '2001234561')
            ->set('account.bankCode', '2010')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $business = Business::query()->where('user_id', $this->user->id)->sole();
        $this->assertSame(self::VALID_COMPANY_NUMBER, $business->company_number);
        $this->assertSame('Brno', Address::query()->where('business_id', $business->id)->value('city'));
        $account = BankAccount::query()->where('business_id', $business->id)->sole();
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame($account->id, $business->fresh()?->default_bank_account_id);
        $this->assertSame($business->id, $this->user->fresh()?->default_business_id);
    }

    #[Test]
    public function a_business_needs_only_its_name(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Jen název')
            ->call('save')
            ->assertHasNoErrors();

        $business = Business::query()->where('user_id', $this->user->id)->sole();
        $this->assertNull($business->company_number);
        $this->assertSame(0, Address::query()->where('business_id', $business->id)->count());
        $this->assertSame(0, BankAccount::query()->where('business_id', $business->id)->count());
    }

    #[Test]
    public function refuses_a_czech_company_number_with_a_wrong_checksum(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', '27082441')
            ->call('save')
            ->assertHasErrors(['party.companyNumber']);

        $this->assertSame(0, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function a_company_number_needs_its_registration_country(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->set('party.country', '')
            ->set('party.companyNumber', 'HRB 12345')
            ->call('save')
            ->assertHasErrors(['party.registrationCountry']);
    }

    #[Test]
    public function the_registration_country_follows_the_address_while_it_is_empty(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.country', 'AT')
            ->set('party.companyNumber', 'FN 12345a')
            ->assertSet('party.registrationCountry', 'AT')
            ->set('party.country', 'DE')
            ->assertSet('party.registrationCountry', 'AT');
    }

    #[Test]
    public function an_address_part_needs_the_country(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->set('party.city', 'Brno')
            ->set('party.country', '')
            ->call('save')
            ->assertHasErrors(['party.country']);
    }

    #[Test]
    public function refuses_an_incomplete_first_account(): void
    {
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->set('account.number', '2001234561')
            ->call('save')
            ->assertHasErrors(['account.number']);

        $this->assertSame(0, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function a_czech_company_number_of_another_business_blocks_the_save_and_points_to_it(): void
    {
        $existing = Business::factory()->archived()->create([
            'user_id' => $this->user->id,
            'company_number' => self::VALID_COMPANY_NUMBER,
        ]);

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Kopie')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', self::VALID_COMPANY_NUMBER)
            ->call('save')
            ->assertSet('blockingDuplicate.archived', true)
            ->assertSet('blockingDuplicate.url', route('frontend.businesses.show', ['business' => $existing->id]))
            ->assertNoRedirect();

        $this->assertSame(1, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function a_matching_name_only_warns_and_saves_once_confirmed(): void
    {
        Business::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Acme',
        ]);

        $form = Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->call('save')
            ->assertCount('duplicateWarnings', 1)
            ->assertNoRedirect();
        $this->assertSame(1, Business::query()->where('user_id', $this->user->id)->count());

        $form->call('confirmDuplicates')->assertRedirect();
        $this->assertSame(2, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function the_capacity_limit_stops_a_new_business(): void
    {
        $this->limitActive('business', 1);
        Business::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Druhá')
            ->call('save')
            ->assertSet('limitMessage', __('businesses.messages.limit_exceeded'))
            ->assertNoRedirect();

        $this->assertSame(1, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function emptied_optional_fields_are_stored_empty(): void
    {
        $business = Business::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'old@example.test',
            'phone' => '+420 123',
        ]);
        Address::factory()->create(['business_id' => $business->id]);

        Livewire::test(BusinessForm::class, ['businessId' => $business->id])
            ->assertSet('party.email', 'old@example.test')
            ->set('party.email', '')
            ->set('party.phone', '')
            ->set('party.street', '')
            ->set('party.city', '')
            ->set('party.postalCode', '')
            ->call('save')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertNull($business->email);
        $this->assertNull($business->phone);
        $this->assertSame(0, Address::query()->where('business_id', $business->id)->count());
    }

    #[Test]
    public function stores_the_logo_and_replaces_it_on_edit(): void
    {
        Storage::fake('public');

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'S logem')
            ->set('logo', UploadedFile::fake()->image('logo.png', 200, 100))
            ->call('save')
            ->assertHasNoErrors();
        $business = Business::query()->where('user_id', $this->user->id)->sole();
        $first = $business->logo;
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        Livewire::test(BusinessForm::class, ['businessId' => $business->id])
            ->set('logo', UploadedFile::fake()->image('new.png', 300, 120))
            ->call('save')
            ->assertHasNoErrors();
        $second = $business->fresh()?->logo;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
    }

    #[Test]
    public function refuses_a_logo_that_is_not_an_image(): void
    {
        Storage::fake('public');

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Bez loga')
            ->set('logo', UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'))
            ->call('save')
            ->assertHasErrors(['logo']);

        $this->assertSame(0, Business::query()->where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function creating_needs_the_permission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BusinessForm::class)->assertForbidden();
    }
}
