<?php

namespace Tests\Feature\Domain\Party\Observers;

use App\Domain\User\Events\UserDataChanged;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Traits\RefreshDatabaseWithData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SupplierObserverFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    #[Test]
    public function supplier_observer_dispatches_events_on_lifecycle_and_only_on_relevant_updates(): void
    {
        Event::fake([UserDataChanged::class]);

        $user = User::factory()->create();

        // Create (should dispatch once)
        $supplier = Supplier::factory()->create([
            'user_id' => $user->id,
            'name' => 'Supplier One',
            'email' => 's1@example.com',
        ]);
        Event::assertDispatchedTimes(UserDataChanged::class, 1);

        // Update non-watched field (city) -> should NOT dispatch
        $supplier->update(['city' => 'Brno']);
        Event::assertDispatchedTimes(UserDataChanged::class, 1);

        // Update watched field (name) -> dispatch
        $supplier->update(['name' => 'Supplier One Renamed']);
        Event::assertDispatchedTimes(UserDataChanged::class, 2);

        // Update watched field (email) -> dispatch
        $supplier->update(['email' => 'supplier-new@example.com']);
        Event::assertDispatchedTimes(UserDataChanged::class, 3);

        // Delete -> dispatch
        $supplier->delete();
        Event::assertDispatchedTimes(UserDataChanged::class, 4);

        Event::assertDispatched(UserDataChanged::class, function (UserDataChanged $event) use ($user) {
            return $event->userId === $user->id && $event->changeType === 'supplier';
        });
    }
}
