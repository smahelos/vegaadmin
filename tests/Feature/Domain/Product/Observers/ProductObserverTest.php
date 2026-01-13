<?php

namespace Tests\Feature\Domain\Product\Observers;

use App\Domain\Shared\Events\FormDataChanged;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductObserverTest extends TestCase
{
    use RefreshDatabaseWithData;

    #[Test]
    public function product_observer_dispatches_events_on_lifecycle_and_only_on_relevant_updates(): void
    {
        Event::fake([FormDataChanged::class]);
        $user = User::factory()->create();

    $product = Product::factory()->create(['user_id' => $user->id, 'name' => 'Prod A', 'price' => 10]);
    // Product create triggers ProductObserver + possibly other observers; assert at least once
    Event::assertDispatched(FormDataChanged::class);

        // Non watched field update (e.g. description) - no dispatch
    $countAfterCreate = Event::dispatched(FormDataChanged::class)->count();
    $product->update(['description' => 'Some desc']);
    // No new dispatch for non-watched field
    Event::assertDispatchedTimes(FormDataChanged::class, $countAfterCreate);

        // Watched field name
    $product->update(['name' => 'Prod B']);
    Event::assertDispatchedTimes(FormDataChanged::class, $countAfterCreate + 1);

        // Watched field price
    $product->update(['price' => 25]);
    Event::assertDispatchedTimes(FormDataChanged::class, $countAfterCreate + 2);

        // Delete
    $product->delete();
    Event::assertDispatchedTimes(FormDataChanged::class, $countAfterCreate + 3);
    }
}
