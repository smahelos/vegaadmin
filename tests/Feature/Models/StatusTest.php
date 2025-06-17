<?php

namespace Tests\Feature\Models;

use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    #[Test]
    public function model_can_be_created_with_factory(): void
    {
        $status = Status::factory()->create();

        $this->assertInstanceOf(Status::class, $status);
        $this->assertNotEmpty($status->name);
        $this->assertNotEmpty($status->slug);
        $this->assertNotNull($status->category_id);
        $this->assertIsBool($status->is_active);
        
        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'name' => $status->name,
            'slug' => $status->slug,
        ]);
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
        $category = StatusCategory::factory()->create();
        
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
        $status = Status::factory()->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $status->update([
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        $this->assertDatabaseHas('statuses', [
            'id' => $status->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);

        $this->assertDatabaseMissing('statuses', [
            'id' => $status->id,
            'name' => 'Original Name',
        ]);
    }

    #[Test]
    public function can_delete_status(): void
    {
        $status = Status::factory()->create();
        $statusId = $status->id;

        $status->delete();

        $this->assertDatabaseMissing('statuses', [
            'id' => $statusId,
        ]);
    }

    #[Test]
    public function slug_must_be_unique(): void
    {
        Status::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(QueryException::class);
        
        Status::factory()->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function can_query_active_statuses(): void
    {
        $activeStatus1 = Status::factory()->active()->create();
        $activeStatus2 = Status::factory()->active()->create();
        $inactiveStatus = Status::factory()->inactive()->create();

        $activeStatuses = Status::active()->get();

        $this->assertCount(2, $activeStatuses);
        $this->assertTrue($activeStatuses->contains($activeStatus1));
        $this->assertTrue($activeStatuses->contains($activeStatus2));
        $this->assertFalse($activeStatuses->contains($inactiveStatus));
    }

    #[Test]
    public function can_query_statuses_by_category(): void
    {
        $category1 = StatusCategory::factory()->create();
        $category2 = StatusCategory::factory()->create();
        
        $status1 = Status::factory()->create(['category_id' => $category1->id]);
        $status2 = Status::factory()->create(['category_id' => $category1->id]);
        $status3 = Status::factory()->create(['category_id' => $category2->id]);

        $category1Statuses = Status::where('category_id', $category1->id)->get();

        $this->assertCount(2, $category1Statuses);
        $this->assertTrue($category1Statuses->contains($status1));
        $this->assertTrue($category1Statuses->contains($status2));
        $this->assertFalse($category1Statuses->contains($status3));
    }

    #[Test]
    public function can_load_category_relationship(): void
    {
        $category = StatusCategory::factory()->create(['name' => 'Test Category']);
        $status = Status::factory()->create(['category_id' => $category->id]);

        $statusWithCategory = Status::with('category')->find($status->id);

        $this->assertNotNull($statusWithCategory->category);
        $this->assertEquals('Test Category', $statusWithCategory->category->name);
        $this->assertEquals($category->id, $statusWithCategory->category->id);
    }

    #[Test]
    public function can_access_invoices_relationship(): void
    {
        $status = Status::factory()->create();

        $invoicesRelation = $status->invoices();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $invoicesRelation);
    }

    #[Test]
    public function can_perform_bulk_operations(): void
    {
        $category = StatusCategory::factory()->create();
        
        Status::factory()->count(5)->create(['category_id' => $category->id, 'is_active' => true]);

        Status::where('category_id', $category->id)->update(['is_active' => false]);

        $this->assertEquals(0, Status::active()->count());
        $this->assertEquals(5, Status::where('is_active', false)->count());
    }

    #[Test]
    public function can_order_statuses_by_name(): void
    {
        Status::factory()->create(['name' => 'Zebra Status']);
        Status::factory()->create(['name' => 'Alpha Status']);
        Status::factory()->create(['name' => 'Beta Status']);

        $statusesOrderedByName = Status::orderBy('name')->pluck('name')->toArray();

        $expectedOrder = ['Alpha Status', 'Beta Status', 'Zebra Status'];
        $this->assertEquals($expectedOrder, $statusesOrderedByName);
    }

    #[Test]
    public function can_search_statuses_by_name(): void
    {
        $category = StatusCategory::factory()->create();
        
        Status::create([
            'name' => 'Payment Due',
            'slug' => 'payment-due',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        
        Status::create([
            'name' => 'Pending Payment',
            'slug' => 'pending-payment-test',
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        
        Status::create([
            'name' => 'Cancelled Status',
            'slug' => 'cancelled-status-test',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // Use case insensitive search for "payment"
        $searchResults = Status::whereRaw('LOWER(name) LIKE ?', ['%payment%'])->get();

        $this->assertCount(2, $searchResults);
        $this->assertTrue($searchResults->pluck('name')->contains('Payment Due'));
        $this->assertTrue($searchResults->pluck('name')->contains('Pending Payment'));
        $this->assertFalse($searchResults->pluck('name')->contains('Cancelled Status'));
    }

    #[Test]
    public function can_filter_by_active_status(): void
    {
        Status::factory()->count(3)->active()->create();
        Status::factory()->count(2)->inactive()->create();

        $activeCount = Status::where('is_active', true)->count();
        $inactiveCount = Status::where('is_active', false)->count();

        $this->assertEquals(3, $activeCount);
        $this->assertEquals(2, $inactiveCount);
    }

    #[Test]
    public function status_has_timestamps(): void
    {
        $status = Status::factory()->create();

        $this->assertNotNull($status->created_at);
        $this->assertNotNull($status->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $status->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $status->updated_at);
    }

    #[Test]
    public function can_create_status_with_minimal_data(): void
    {
        $category = StatusCategory::factory()->create();
        
        $status = Status::create([
            'name' => 'Minimal Status',
            'slug' => 'minimal-status',
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('statuses', [
            'name' => 'Minimal Status',
            'slug' => 'minimal-status',
            'category_id' => $category->id,
        ]);

        $this->assertNotNull($status->id);
        $this->assertEquals('Minimal Status', $status->name);
        $this->assertEquals('minimal-status', $status->slug);
    }

    #[Test]
    public function is_active_cast_to_boolean(): void
    {
        $status = Status::factory()->create(['is_active' => 1]);
        
        $this->assertIsBool($status->is_active);
        $this->assertTrue($status->is_active);
        
        $status->update(['is_active' => 0]);
        $status->refresh();
        
        $this->assertIsBool($status->is_active);
        $this->assertFalse($status->is_active);
    }
}
