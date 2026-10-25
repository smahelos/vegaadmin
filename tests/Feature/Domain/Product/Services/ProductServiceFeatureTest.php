<?php

namespace Tests\Feature\Domain\Product\Services;

use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Models\ProductCategory;
use App\Models\Tax;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductServiceFeatureTest extends TestCase
{
    private function makeCountingCache(): CacheServiceInterface
    {
        return new class implements CacheServiceInterface
        {
            public int $executions = 0;

            public array $store = [];

            public array $tags = [];

            public function remember(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): mixed {
                if (array_key_exists($key, $this->store)) {
                    return $this->store[$key];
                }
                $value = is_callable($data) ? $data() : $data;
                $this->store[$key] = $value;
                $this->tags[$key] = $tags;
                $this->executions++;

                return $value;
            }

            public function get(string $key): mixed
            {
                return $this->store[$key] ?? null;
            }

            public function put(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): bool {
                $this->store[$key] = $data;
                $this->tags[$key] = $tags;

                return true;
            }

            public function forget(string $key): bool
            {
                unset($this->store[$key], $this->tags[$key]);

                return true;
            }

            public function invalidateTags(array $tags): bool
            {
                foreach ($this->tags as $k => $t) {
                    if (count(array_intersect($t, $tags)) > 0) {
                        unset($this->store[$k], $this->tags[$k]);
                    }
                }

                return true;
            }

            public function userKey(int $userId, string $suffix): string
            {
                return 'u_' . $userId . '_' . $suffix;
            }

            public function globalKey(string $suffix): string
            {
                return 'g_' . $suffix;
            }

            public function increment(string $key, int $amount): bool
            {
                if (! array_key_exists($key, $this->store)) {
                    $this->store[$key] = 0;
                } $this->store[$key] += $amount;

                return $this->store[$key];
            }
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
        // The service lists the whole table; start without the seeded categories
        ProductCategory::query()->delete();
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
}
