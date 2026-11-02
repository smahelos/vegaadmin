<?php

namespace Tests\Feature\Models;

use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusFeatureTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // The queries below read the whole statuses table; start without the seeded statuses
        $statusQuery = Status::query();
        $statusQuery->delete();
    }

    #[Test]
    public function model_can_be_created_with_factory(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create();

        $this->assertInstanceOf(Status::class, $status);
        $this->assertNotEmpty($status->name);
        $this->assertNotEmpty($status->slug);
        $this->assertNotNull($status->category_id);
        $this->assertIsBool($status->is_active);

        $this->assertDatabaseHas(
            'statuses',
            [
                'id' => $status->id,
                'name' => $status->name,
                'slug' => $status->slug,
            ],
        );
    }

    #[Test]
    public function status_factory_can_create_active_status(): void
    {
        $status = Status::factory()->active()->create();

        $this->assertTrue($status->is_active);
        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function status_factory_can_create_inactive_status(): void
    {
        $status = Status::factory()->inactive()->create();

        $this->assertFalse($status->is_active);
        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function can_create_status_through_database(): void
    {
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create();

        $statusData = [
            'name' => 'Test Status',
            'slug' => 'test-status',
            'category_id' => $category->id,
            'color' => 'bg-green-100 text-green-800',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $status = Status::create($statusData);

        $this->assertDatabaseHas('statuses', $statusData);
        $this->assertEquals('Test Status', $status->name);
        $this->assertEquals('test-status', $status->slug);
    }

    #[Test]
    public function can_update_status(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $status->update([
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        $this->assertDatabaseHas(
            'statuses',
            [
                'id' => $status->id,
                'name' => 'Updated Name',
                'slug' => 'updated-name',
            ],
        );

        $this->assertDatabaseMissing('statuses', [
            'id' => $status->id,
            'name' => 'Original Name',
        ]);
    }

    #[Test]
    public function can_delete_status(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create();
        $statusId = $status->id;

        $status->delete();

        $this->assertDatabaseMissing('statuses', [
            'id' => $statusId,
        ]);
    }

    #[Test]
    public function slug_must_be_unique(): void
    {
        $statusFactory = Status::factory();
        $statusFactory->create(['slug' => 'unique-slug']);

        $this->expectException(QueryException::class);

        $statusFactory->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function can_query_statuses_by_category(): void
    {
        $statusCategoryFactory = StatusCategory::factory();
        $category1 = $statusCategoryFactory->create();
        $category2 = $statusCategoryFactory->create();

        $statusFactory = Status::factory();
        $status1 = $statusFactory->create(['category_id' => $category1->id]);
        $status2 = $statusFactory->create(['category_id' => $category1->id]);
        $status3 = $statusFactory->create(['category_id' => $category2->id]);

        $statusQuery = Status::where('category_id', $category1->id);
        $category1Statuses = $statusQuery->get();

        $this->assertCount(2, $category1Statuses);
        $this->assertTrue(
            $category1Statuses->contains($status1),
        );
        $this->assertTrue(
            $category1Statuses->contains($status2),
        );
        $this->assertFalse(
            $category1Statuses->contains($status3),
        );
    }

    #[Test]
    public function can_load_category_relationship(): void
    {
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create(['name' => 'Test Category']);
        $statusFactory = Status::factory();
        $status = $statusFactory->create(['category_id' => $category->id]);

        $statusQuery = Status::with('category');
        $statusWithCategory = $statusQuery->find($status->id);

        $this->assertNotNull($statusWithCategory->category);
        $this->assertEquals('Test Category', $statusWithCategory->category->name);
        $this->assertEquals($category->id, $statusWithCategory->category->id);
    }

    #[Test]
    public function can_access_invoices_relationship(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create();

        $invoicesRelation = $status->invoices();

        $this->assertInstanceOf(HasMany::class, $invoicesRelation);
    }

    #[Test]
    public function can_order_statuses_by_name(): void
    {
        $statusFactory = Status::factory();
        $statusFactory->create(['name' => 'Zebra Status']);
        $statusFactory->create(['name' => 'Alpha Status']);
        $statusFactory->create(['name' => 'Beta Status']);

        $statusQuery = Status::orderBy('name');
        $statusValues = $statusQuery->pluck('name');
        $statusesOrderedByName = $statusValues->toArray();

        $expectedOrder = [
            'Alpha Status',
            'Beta Status',
            'Zebra Status',
        ];
        $this->assertEquals($expectedOrder, $statusesOrderedByName);
    }

    #[Test]
    public function can_search_statuses_by_name(): void
    {
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create();

        Status::create(
            [
                'name' => 'Payment Due',
                'slug' => 'payment-due',
                'category_id' => $category->id,
                'is_active' => true,
            ],
        );

        Status::create(
            [
                'name' => 'Pending Payment',
                'slug' => 'pending-payment-test',
                'category_id' => $category->id,
                'is_active' => true,
            ],
        );

        Status::create(
            [
                'name' => 'Cancelled Status',
                'slug' => 'cancelled-status-test',
                'category_id' => $category->id,
                'is_active' => true,
            ],
        );

        // Use case insensitive search for "payment"
        $statusQuery = Status::whereRaw('LOWER(name) LIKE ?', ['%payment%']);
        $searchResults = $statusQuery->get();

        $this->assertCount(2, $searchResults);
        $this->assertTrue(
            $searchResults->pluck('name')->contains('Payment Due'),
        );
        $this->assertTrue(
            $searchResults->pluck('name')->contains('Pending Payment'),
        );
        $this->assertFalse(
            $searchResults->pluck('name')->contains('Cancelled Status'),
        );
    }

    #[Test]
    public function can_filter_by_active_status(): void
    {
        Status::factory()->count(3)->active()->create();
        Status::factory()->count(2)->inactive()->create();

        $statusQuery = Status::where('is_active', true);
        $activeCount = $statusQuery->count();
        $statusQuery = Status::where('is_active', false);
        $inactiveCount = $statusQuery->count();

        $this->assertEquals(3, $activeCount);
        $this->assertEquals(2, $inactiveCount);
    }

    #[Test]
    public function status_has_timestamps(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create();

        $this->assertNotNull($status->created_at);
        $this->assertNotNull($status->updated_at);
        $this->assertInstanceOf(Carbon::class, $status->created_at);
        $this->assertInstanceOf(Carbon::class, $status->updated_at);
    }

    #[Test]
    public function can_create_status_with_minimal_data(): void
    {
        $statusCategoryFactory = StatusCategory::factory();
        $category = $statusCategoryFactory->create();

        $status = Status::create(
            [
                'name' => 'Minimal Status',
                'slug' => 'minimal-status',
                'category_id' => $category->id,
            ],
        );

        $this->assertDatabaseHas(
            'statuses',
            [
                'name' => 'Minimal Status',
                'slug' => 'minimal-status',
                'category_id' => $category->id,
            ],
        );

        $this->assertNotNull($status->id);
        $this->assertEquals('Minimal Status', $status->name);
        $this->assertEquals('minimal-status', $status->slug);
    }

    #[Test]
    public function is_active_cast_to_boolean(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create(['is_active' => 1]);

        $this->assertIsBool($status->is_active);
        $this->assertTrue($status->is_active);

        $status->update(['is_active' => 0]);
        $status->refresh();

        $this->assertIsBool($status->is_active);
        $this->assertFalse($status->is_active);
    }

    #[Test]
    public function color_preview_is_a_badge_in_the_status_color(): void
    {
        $statusFactory = Status::factory();
        $status = $statusFactory->create(['name' => 'Paid <b>', 'color' => 'green']);

        $this->assertSame(
            '<span class="badge bg-green text-green-fg">Paid &lt;b&gt;</span>',
            $status->color_preview,
        );
    }

    #[Test]
    public function gray_or_missing_color_uses_the_secondary_badge(): void
    {
        $statusFactory = Status::factory();
        $gray = $statusFactory->create(['color' => 'gray']);
        $none = $statusFactory->create(['color' => null]);

        $this->assertStringContainsString('badge bg-secondary text-secondary-fg', $gray->color_preview);
        $this->assertStringContainsString('badge bg-secondary text-secondary-fg', $none->color_preview);
        // no Tailwind default: the frontend falls back to its own color map
        $this->assertNull($none->color);
    }
}
