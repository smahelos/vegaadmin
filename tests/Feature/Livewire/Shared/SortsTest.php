<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Party\ClientList;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SortsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Older Client ' . uniqid(),
            'created_at' => now()->subDays(2),
        ]);
        Client::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Newer Client ' . uniqid(),
            'created_at' => now()->subDay(),
        ]);
    }

    #[Test]
    public function sorts_by_an_allowed_key(): void
    {
        Livewire::test(ClientList::class)
            ->call('sortBy', 'name')
            ->assertSet('orderBy', 'name')
            ->assertSet('orderAsc', true)
            ->assertSeeInOrder(['Newer Client', 'Older Client']);
    }

    #[Test]
    public function sorts_a_key_by_its_mapped_column(): void
    {
        $older = Client::query()->where('user_id', $this->user->id)->where(
            'name',
            'like',
            'Older%',
        )->firstOrFail();
        Invoice::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'client_id' => $older->id,
        ]);

        Livewire::test(ClientList::class)
            ->call('sortBy', 'invoices')
            ->call('sortBy', 'invoices')
            ->assertSet('orderAsc', false)
            ->assertSet('errorMessage', null)
            ->assertSeeInOrder(['Older Client', 'Newer Client']);
    }

    #[Test]
    public function ignores_a_key_the_list_does_not_offer(): void
    {
        Livewire::test(ClientList::class)
            ->call('sortBy', 'user_id')
            ->assertSet('orderBy', 'created_at')
            ->assertSet('orderAsc', false);
    }

    #[Test]
    public function an_unknown_key_from_the_url_falls_back_to_the_default_order(): void
    {
        Livewire::withQueryParams(['sort' => 'password', 'direction' => '1'])
            ->test(ClientList::class)
            ->assertSet('errorMessage', null)
            ->assertSeeInOrder(['Older Client', 'Newer Client']);
    }
}
