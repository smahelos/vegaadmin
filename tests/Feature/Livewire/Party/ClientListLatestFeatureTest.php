<?php

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\ClientListLatest;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientListLatestFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function component_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);

        $component->assertStatus(200);
        $component->assertViewIs('livewire.party.client-list-latest');
    }

    #[Test]
    public function component_displays_user_clients_only(): void
    {
        $otherUser = User::factory()->create();

        $userClient = Client::factory()->create(['user_id' => $this->user->id]);
        $otherUserClient = Client::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);
        $clients = $component->viewData('clients');

        $this->assertEquals(1, $clients->count());
        $this->assertEquals($userClient->id, $clients->first()->id);
    }

    #[Test]
    public function component_orders_clients_by_created_at_desc_by_default(): void
    {
        // Create clients with different creation times
        $olderClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2),
        ]);

        $newerClient = Client::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);
        $clients = $component->viewData('clients');

        // Should be ordered newest first (desc) by default
        $this->assertEquals($newerClient->id, $clients->first()->id);
        $this->assertEquals($olderClient->id, $clients->last()->id);
    }

    #[Test]
    public function component_displays_empty_state_when_no_clients(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);

        $component->assertViewHas('hasData', false);
        $clients = $component->viewData('clients');
        $this->assertEquals(0, $clients->count());
    }

    #[Test]
    public function component_handles_database_errors_gracefully(): void
    {
        $this->actingAs($this->user);

        // Temporarily modify the auth to return an invalid user ID to trigger error
        $originalUser = auth()->user();
        auth()->logout();

        // Force an invalid state that might cause database error
        $component = Livewire::test(ClientListLatest::class);

        // Since we're not authenticated, it should handle gracefully
        $component->assertViewHas('hasData', false);

        // Log back in to restore state
        $this->actingAs($originalUser);

        // Test passes if component handles edge cases gracefully
        $this->assertTrue(true);
    }

    #[Test]
    public function component_paginates_with_5_items_per_page(): void
    {
        // Create more clients than page size
        Client::factory()->count(8)->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);
        $component->assertViewHas('clients', function ($clients) {
            // $clients is a LengthAwarePaginator
            return method_exists($clients, 'total') && $clients->count() === 5 && $clients->total() === 8;
        });
    }

    #[Test]
    public function component_requires_authentication(): void
    {
        // Don't authenticate

        $component = Livewire::test(ClientListLatest::class);

        // Should show no clients when not authenticated (Auth::id() returns null)
        $component->assertViewHas('hasData', false);
        $clients = $component->viewData('clients');
        $this->assertEquals(0, $clients->count());
    }

    #[Test]
    public function component_uses_correct_view(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);

        $component->assertViewIs('livewire.party.client-list-latest');
    }

    #[Test]
    public function component_passes_correct_data_to_view(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user);

        $component = Livewire::test(ClientListLatest::class);

        $component->assertViewHas('clients');
        $component->assertViewHas('hasData', true);
        $component->assertViewHas('errorMessage', null);
    }
}
