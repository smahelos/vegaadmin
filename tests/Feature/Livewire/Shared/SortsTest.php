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

        $alpha = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Alpha Contact',
        ]);
        $beta = Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Beta Contact',
        ]);
        Address::factory()->create([
            'business_id' => null,
            'contact_id' => $alpha->id,
            'city' => 'Zlín',
        ]);
        Address::factory()->create([
            'business_id' => null,
            'contact_id' => $beta->id,
            'city' => 'Brno',
        ]);
    }

    #[Test]
    public function sorts_by_an_allowed_key_and_toggles_the_direction(): void
    {
        Livewire::test(ContactList::class)
            ->assertSeeInOrder(['Alpha Contact', 'Beta Contact'])
            ->call('sortBy', 'name')
            ->assertSet('orderAsc', false)
            ->assertSeeInOrder(['Beta Contact', 'Alpha Contact']);
    }

    #[Test]
    public function sorts_a_key_by_its_mapped_column(): void
    {
        Livewire::test(ContactList::class)
            ->call('sortBy', 'city')
            ->assertSet('orderBy', 'city')
            ->assertSet('orderAsc', true)
            ->assertSeeInOrder(['Beta Contact', 'Alpha Contact']);
    }

    #[Test]
    public function ignores_a_key_the_list_does_not_offer(): void
    {
        Livewire::test(ContactList::class)
            ->call('sortBy', 'user_id')
            ->assertSet('orderBy', 'name')
            ->assertSet('orderAsc', true);
    }

    #[Test]
    public function an_unknown_key_from_the_url_falls_back_to_the_default_order(): void
    {
        Livewire::withQueryParams(['sort' => 'password', 'direction' => '1'])
            ->test(ContactList::class)
            ->assertSeeInOrder(['Alpha Contact', 'Beta Contact']);
    }
}
