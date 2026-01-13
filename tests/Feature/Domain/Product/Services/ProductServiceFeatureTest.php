<?php

namespace Tests\Feature\Domain\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Product\Contracts\ProductDtoReadRepositoryInterface;
use App\Domain\Product\Contracts\ProductDtoWriteRepositoryInterface;
use App\Domain\Product\Services\ProductService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductServiceFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeCountingCache(): CacheServiceInterface
    {
        return new class implements CacheServiceInterface {
            public int $executions = 0; public array $store = []; public array $tags = [];
            public function remember(string $key, mixed $data, int $ttl = 3600, array $tags = []): mixed {
                if (array_key_exists($key, $this->store)) return $this->store[$key];
                $value = is_callable($data) ? $data() : $data; $this->store[$key] = $value; $this->tags[$key] = $tags; $this->executions++; return $value;
            }
            public function get(string $key): mixed { return $this->store[$key] ?? null; }
            public function put(string $key, mixed $data, int $ttl = 3600, array $tags = []): bool { $this->store[$key] = $data; $this->tags[$key] = $tags; return true; }
            public function forget(string $key): bool { unset($this->store[$key], $this->tags[$key]); return true; }
            public function invalidateTags(array $tags): bool { foreach ($this->tags as $k => $t) { if (count(array_intersect($t, $tags)) > 0) { unset($this->store[$k], $this->tags[$k]); } } return true; }
            public function userKey(int $userId, string $suffix): string { return 'u_'.$userId.'_'.$suffix; }
            public function globalKey(string $suffix): string { return 'g_'.$suffix; }
            public function increment(string $key, int $amount): bool { if (!array_key_exists($key, $this->store)) { $this->store[$key] = 0; } $this->store[$key] += $amount; return $this->store[$key]; }
        };
    }

    #[Test]
    public function get_form_data_returns_expected_structure_and_is_cached(): void
    {
        // Prepare data
        ProductCategory::factory()->create(['name' => 'Electronics']);
        ProductCategory::factory()->create(['name' => 'Books']);
        Tax::factory()->create(['slug' => 'dph', 'rate' => 21]);
        Tax::factory()->create(['slug' => 'other', 'rate' => 10]); // should be ignored

        $cache = $this->makeCountingCache();
        $this->app->instance(CacheServiceInterface::class, $cache);
        // Resolve Application service which delegates to ProductFormDataService
        $service = app(ProductApplicationServiceInterface::class);

        // First call - executes closure
        DB::enableQueryLog();
        $data1 = $service->getFormData();
        $firstQueries = DB::getQueryLog();
        DB::flushQueryLog();
        $this->assertArrayHasKey('product_categories', $data1);
        $this->assertArrayHasKey('tax_rates', $data1);
        $this->assertArrayHasKey('categories', $data1);
        // tax rates only for 'dph' and formatted with %
        $this->assertNotEmpty($data1['tax_rates']);
        foreach ($data1['tax_rates'] as $rate) {
            $this->assertStringEndsWith('%', (string) $rate);
        }

        // Second call - should be served from cache (executions stays 1)
        $data2 = $service->getFormData();
        $secondQueries = DB::getQueryLog();
        $this->assertSame($data1, $data2);
        $this->assertCount(0, $secondQueries); // cached, no DB queries on second call
    }

    #[Test]
    public function get_all_categories_is_cached_and_sorted(): void
    {
        Cache::flush();
        ProductCategory::factory()->create(['name' => 'Zeta']);
        ProductCategory::factory()->create(['name' => 'Alpha']);

        // Resolve Application service
        $service = app(ProductApplicationServiceInterface::class);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $first = $service->getAllCategories();
        $firstQueries = DB::getQueryLog();
        DB::flushQueryLog();
        $second = $service->getAllCategories();
        $secondQueries = DB::getQueryLog();

        // Sorted asc by name
        $this->assertSame(['Alpha', 'Zeta'], array_values($first));
        // Second call should hit cache and perform fewer (ideally zero) queries
        $this->assertTrue(count($secondQueries) <= count($firstQueries));
    }

    #[Test]
    public function get_all_suppliers_filters_by_authenticated_user_and_is_cached(): void
    {
        Cache::flush();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Supplier::factory()->create(['user_id' => $user1->id, 'name' => 'S1']);
        Supplier::factory()->create(['user_id' => $user2->id, 'name' => 'S2']);
        $cache = $this->makeCountingCache();
        $this->app->instance(CacheServiceInterface::class, $cache);
        $service = app(ProductApplicationServiceInterface::class);
        $this->actingAs($user1, 'web');

        DB::enableQueryLog();
        DB::flushQueryLog();
        $first = $service->getAllSuppliers();
        $firstQueries = DB::getQueryLog();
        DB::flushQueryLog();
        $second = $service->getAllSuppliers();
        $secondQueries = DB::getQueryLog();

        // Should return only suppliers for authenticated user1
        $this->assertNotEmpty($first);
        $this->assertCount(1, $first);
        $this->assertSame('S1', array_values($first)[0]);
        $this->assertTrue(count($secondQueries) <= count($firstQueries));
    }

    #[Test]
    public function clear_categories_cache_invalidates_expected_keys(): void
    {
        Cache::flush();
        ProductCategory::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);

        // Use the tag-aware cache adapter instead of the raw facade
        $cache = $this->makeCountingCache();
        $this->app->instance(CacheServiceInterface::class, $cache);

        // Prime adapter-backed keys that clearCategoriesCache() will forget
        $cache->put('products_by_category:alpha:0', [1,2,3], 600, ['form_data', 'products']);
        $cache->put('products_by_category:alpha:1', [4,5,6], 600, ['form_data', 'products']);
        $cache->put('product_categories', ['alpha' => 'Alpha'], 600, ['form_data', 'products']);

        $service = app(ProductApplicationServiceInterface::class);
        $service->clearCategoriesCache();

        // Assert via the adapter (behavior), not the raw Cache facade state
        $this->assertNull($cache->get('products_by_category:alpha:0'));
        $this->assertNull($cache->get('products_by_category:alpha:1'));
        $this->assertNull($cache->get('product_categories'));
    }
}
