<?php

namespace Tests\Feature\Domain\Party\Observers;

use App\Domain\User\Events\UserDataChanged;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientObserverFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    #[Test]
    public function client_observer_dispatches_events_on_lifecycle_and_only_on_relevant_updates(): void
    {
        Event::fake([UserDataChanged::class]);

        $user = User::factory()->create();

        // Create (should dispatch once)
        $client = Client::factory()->create([
            'user_id' => $user->id,
            'name' => 'Client One',
            'email' => 'c1@example.com',
        ]);
        Event::assertDispatchedTimes(UserDataChanged::class, 1);

        // Update non-watched field (city) -> should NOT dispatch
        $client->update(['city' => 'Prague']);
        Event::assertDispatchedTimes(UserDataChanged::class, 1);

        // Update watched field (name) -> should dispatch
        $client->update(['name' => 'Client One Renamed']);
        Event::assertDispatchedTimes(UserDataChanged::class, 2);

        // Update watched field (email) -> dispatch again
        $client->update(['email' => 'new-email@example.com']);
        Event::assertDispatchedTimes(UserDataChanged::class, 3);

        // Delete -> dispatch
        $client->delete();
        Event::assertDispatchedTimes(UserDataChanged::class, 4);

        Event::assertDispatched(UserDataChanged::class, function (UserDataChanged $event) use ($user) {
            return $event->userId === $user->id && $event->changeType === 'client';
        });
    }
}
