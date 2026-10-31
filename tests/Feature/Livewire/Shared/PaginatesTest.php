<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Party\ContactList;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class PaginatesTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('cs');
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        Contact::factory()->count(15)->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function list_shows_translated_pagination(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ContactList::class)
            ->assertSeeText('Zobrazeno')
            ->assertSee('aria-label="Další"', false)
            ->assertDontSeeText('Showing')
            ->assertDontSeeText('results');
    }

    #[Test]
    public function next_page_moves_the_list_without_a_page_link(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ContactList::class)
            ->assertSee("wire:click=\"nextPage('page')\"", false)
            ->assertDontSee('href="?page=', false)
            ->call('nextPage')
            ->assertSee('aria-current="page">2</span>', false)
            ->assertSee('<span class="font-medium">11</span>', false);
    }

    #[Test]
    public function page_from_the_url_that_is_not_a_number_shows_the_first_page(): void
    {
        $this->actingAs($this->user);

        Livewire::withQueryParams(['page' => 'abc'])
            ->test(ContactList::class)
            ->assertOk()
            ->assertSee('aria-current="page">1</span>', false);
    }
}
