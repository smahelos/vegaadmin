<?php

namespace Tests\Feature\Domain\Shared\Status\Services;

use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithData;
use App\Domain\Shared\Status\Contracts\StatusServiceInterface;
use App\Models\StatusCategory;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use App\Domain\Shared\Status\ValueObjects\StatusCode;

class StatusServiceFeatureTest extends TestCase
{
    use RefreshDatabaseWithData;

    public function test_category_list_is_cached_and_reused(): void
    {
        $service = app(StatusServiceInterface::class);

        $initial = StatusCategory::count();
        StatusCategory::factory()->count(2)->create();
        $expectedFirst = $initial + 2;

        $first = $service->getAllCategories();
        $this->assertCount($expectedFirst, $first, 'First fetch should include initial + 2 newly created categories');

        // Add new category AFTER cache populated
        StatusCategory::factory()->create();
        $cached = $service->getAllCategories();
        $this->assertCount($expectedFirst, $cached, 'Cache should reuse first result (no new category yet)');

        $service->clearStatusCaches();
        $refreshed = $service->getAllCategories();
        $this->assertCount($expectedFirst + 1, $refreshed, 'Cache should refresh after invalidation and include the newly added category');
    }

    public function test_id_and_slug_maps_cached_and_invalidated(): void
    {
        $service = app(StatusServiceInterface::class);

        $initial = Status::count();
        Status::factory()->count(3)->create();
        $expectedInitialMapSize = $initial + 3;

        $idToSlug = $service->getIdToSlugMap();
        $slugToId = $service->getSlugToIdMap();
        $this->assertCount($expectedInitialMapSize, $idToSlug, 'Initial id->slug map size mismatch');
        $this->assertCount($expectedInitialMapSize, $slugToId, 'Initial slug->id map size mismatch');

        // Create new status AFTER initial cache
        Status::factory()->create();
        $stillCached = $service->getIdToSlugMap();
        $this->assertCount($expectedInitialMapSize, $stillCached, 'Cache should not yet include the newly created status');

        $service->clearStatusCaches();
        $updated = $service->getIdToSlugMap();
        $this->assertCount($expectedInitialMapSize + 1, $updated, 'After invalidation cache should include newly created status');
    }

    public function test_provides_slug_maps(): void
    {
        $service = app(StatusServiceInterface::class);

        $idToSlug = $service->getIdToSlugMap();
        $slugToId = $service->getSlugToIdMap();

        $this->assertIsArray($idToSlug);
        $this->assertIsArray($slugToId);

        foreach (array_slice($idToSlug, 0, 5, true) as $id => $slug) {
            $this->assertArrayHasKey($slug, $slugToId);
            $this->assertSame($id, $slugToId[$slug]);
        }
    }

    public function test_enum_sync_detects_unknown_status(): void
    {
        $service = app(StatusServiceInterface::class);

        // Ensure all enum statuses exist (seed should have them)
        $service->getIdToSlugMap();

        // Remove one required enum status from DB to simulate missing system status
        $missing = StatusCode::APPROVED->value;
        Status::where('slug', $missing)->delete();

        // Clear caches so fresh map is built
        $service->clearStatusCaches();
        Cache::flush();

        // Reset internal enum sync flag via reflection
        $ref = new \ReflectionClass($service);
        $prop = $ref->getProperty('enumSyncChecked');
        $prop->setAccessible(true);
        $prop->setValue($service, false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/'.$missing.'/');
        $service->getIdToSlugMap();
    }
}
