<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Party\ClientList;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PaginatesTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('cs');
        URL::defaults(['locale' => 'cs']);
        $this->user = User::factory()->create();
        Client::factory()->count(15)->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function list_shows_translated_pagination(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ClientList::class)
            ->assertSeeText('Zobrazeno')
            ->assertSee('aria-label="Další"', false)
            ->assertDontSeeText('Showing')
            ->assertDontSeeText('results');
    }

    #[Test]
    public function next_page_moves_the_list_without_a_page_link(): void
    {
        $this->actingAs($this->user);

        Livewire::test(ClientList::class)
            ->assertSee("wire:click=\"nextPage('page')\"", false)
            ->assertDontSee('href="?page=', false)
            ->call('nextPage')
            ->assertSee('aria-current="page">2</span>', false)
            ->assertSee('<span class="font-medium">11</span>', false);
    }
}
