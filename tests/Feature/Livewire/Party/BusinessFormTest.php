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
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Jen název')
            ->call('save')
            ->assertHasNoErrors();

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
        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', '27082441')
            ->call('save')
            ->assertHasErrors(['party.companyNumber']);

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
        $existing = Business::factory()->archived()->create(
            [
                'user_id' => $this->user->id,
                'company_number' => self::VALID_COMPANY_NUMBER,
            ],
        );

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Kopie')
            ->set('party.registrationCountry', 'CZ')
            ->set('party.companyNumber', self::VALID_COMPANY_NUMBER)
            ->call('save')
            ->assertSet('blockingDuplicate.archived', true)
            ->assertSet(
                'blockingDuplicate.url',
                route('frontend.businesses.show', ['business' => $existing->id]),
            )
            ->assertNoRedirect();

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

        $form = Livewire::test(BusinessForm::class)
            ->set('party.name', 'Acme')
            ->call('save')
            ->assertCount('duplicateWarnings', 1)
            ->assertNoRedirect();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $this->assertSame(
            1,
            $businessQuery->count(),
        );

        $form->call('confirmDuplicates')->assertRedirect();
        $businessQuery2 = Business::query();
        $businessQuery2->where('user_id', $this->user->id);
        $this->assertSame(
            2,
            $businessQuery2->count(),
        );
    }

    #[Test]
    public function the_capacity_limit_stops_a_new_business(): void
    {
        $this->limitActive('business', 1);
        $businessFactory = Business::factory();
        $businessFactory->create(['user_id' => $this->user->id]);

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Druhá')
            ->call('save')
            ->assertSet(
                'limitMessage',
                __('businesses.messages.limit_exceeded'),
            )
            ->assertNoRedirect();

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

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'S logem')
            ->set(
                'logo',
                UploadedFile::fake()->image(
                    'logo.png',
                    200,
                    100,
                ),
            )
            ->call('save')
            ->assertHasNoErrors();
        $businessQuery = Business::query();
        $businessQuery->where('user_id', $this->user->id);
        $business = $businessQuery->sole();
        $first = $business->logo;
        $this->assertNotNull($first);
        $disk = Storage::disk('public');
        $disk->assertExists($first);

        Livewire::test(BusinessForm::class, ['businessId' => $business->id])
            ->set(
                'logo',
                UploadedFile::fake()->image(
                    'new.png',
                    300,
                    120,
                ),
            )
            ->call('save')
            ->assertHasNoErrors();
        $second = $business->fresh()?->logo;
        $this->assertNotSame($first, $second);
        $disk2 = Storage::disk('public');
        $disk2->assertMissing($first);
    }

    #[Test]
    public function refuses_a_logo_that_is_not_an_image(): void
    {
        Storage::fake('public');

        Livewire::test(BusinessForm::class)
            ->set('party.name', 'Bez loga')
            ->set(
                'logo',
                UploadedFile::fake()->create(
                    'logo.pdf',
                    10,
                    'application/pdf',
                ),
            )
            ->call('save')
            ->assertHasErrors(['logo']);

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
        $this->actingAs(User::factory()->create());

        Livewire::test(BusinessForm::class)->assertForbidden();
    }
}
