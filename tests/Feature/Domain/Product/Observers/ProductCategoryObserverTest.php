<?php

namespace Tests\Feature\Domain\Product\Observers;

use App\Domain\Shared\Events\FormDataChanged;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;

class ProductCategoryObserverTest extends TestCase
{
    use RefreshDatabaseWithData;

    #[Test]
    public function category_observer_dispatches_events_on_lifecycle_and_only_on_relevant_updates(): void
    {
        Event::fake([FormDataChanged::class]);

        $category = ProductCategory::factory()->create(['name' => 'Cat A']);
        Event::assertDispatchedTimes(FormDataChanged::class, 1);

        // Non watched field (e.g. description if exists) skip - we only have name so simulate no dispatch with another save
        $category->update(['slug' => $category->slug]);
        Event::assertDispatchedTimes(FormDataChanged::class, 1);

        // Watched field name -> dispatch
        $category->update(['name' => 'Cat B']);
        Event::assertDispatchedTimes(FormDataChanged::class, 2);

        // Delete -> dispatch
        $category->delete();
        Event::assertDispatchedTimes(FormDataChanged::class, 3);
    }
}
