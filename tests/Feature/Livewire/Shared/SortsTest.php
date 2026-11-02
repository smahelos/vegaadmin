<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Party\ContactList;
use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class SortsTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);

        $contactFactory = Contact::factory();
        $alpha = $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Alpha Contact',
            ],
        );
        $beta = $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Beta Contact',
            ],
        );
        $addressFactory = Address::factory();
        $addressFactory->create(
            [
                'business_id' => null,
                'contact_id' => $alpha->id,
                'city' => 'Zlín',
            ],
        );
        $addressFactory->create(
            [
                'business_id' => null,
                'contact_id' => $beta->id,
                'city' => 'Brno',
            ],
        );
    }

    #[Test]
    public function sorts_by_an_allowed_key_and_toggles_the_direction(): void
    {
        $component = Livewire::test(ContactList::class);
        $component->assertSeeInOrder(['Alpha Contact', 'Beta Contact']);
        $component->call('sortBy', 'name');
        $component->assertSet('orderAsc', false);
        $component->assertSeeInOrder(['Beta Contact', 'Alpha Contact']);
    }

    #[Test]
    public function sorts_a_key_by_its_mapped_column(): void
    {
        $component = Livewire::test(ContactList::class);
        $component->call('sortBy', 'city');
        $component->assertSet('orderBy', 'city');
        $component->assertSet('orderAsc', true);
        $component->assertSeeInOrder(['Beta Contact', 'Alpha Contact']);
    }

    #[Test]
    public function ignores_a_key_the_list_does_not_offer(): void
    {
        $component = Livewire::test(ContactList::class);
        $component->call('sortBy', 'user_id');
        $component->assertSet('orderBy', 'name');
        $component->assertSet('orderAsc', true);
    }

    #[Test]
    public function an_unknown_key_from_the_url_falls_back_to_the_default_order(): void
    {
        Livewire::withQueryParams(['sort' => 'password', 'direction' => '1']);
        $component = Livewire::test(ContactList::class);
        $component->assertSeeInOrder(['Alpha Contact', 'Beta Contact']);
    }
}
