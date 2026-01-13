<?php

namespace Tests\Feature\Domain\Analytics;

use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyticsCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    /** Simple in-memory tagged cache fake for deterministic behavior. */
    private array $store = [];
    private array $tagIndex = [];

    private function makeFakeCacheService(): CacheServiceInterface
    {
        $storeRef = &$this->store; // Shared store by reference for determinism
        $tagRef = &$this->tagIndex; // Shared tag index for invalidation
        return new class($storeRef, $tagRef) implements CacheServiceInterface {
            public function __construct(private array &$store, private array &$tags) {}
            public function remember(string $key, mixed $data, int $ttl = 3600, array $tags = []): mixed {
                if (array_key_exists($key, $this->store)) {
                    return $this->store[$key]['value'];
                }
                $value = is_callable($data) ? $data() : $data;
                $this->store[$key] = ['value' => $value, 'tags' => $tags];
                foreach ($tags as $tag) { $this->tags[$tag][$key] = true; }
                return $value;
            }
            public function get(string $key): mixed { return $this->store[$key]['value'] ?? null; }
            public function put(string $key, mixed $data, int $ttl = 3600, array $tags = []): bool {
                $this->store[$key] = ['value' => $data, 'tags' => $tags];
                foreach ($tags as $tag) { $this->tags[$tag][$key] = true; }
                return true;
            }
            public function forget(string $key): bool {
                if (!isset($this->store[$key])) return false;
                $tags = $this->store[$key]['tags'];
                unset($this->store[$key]);
                foreach ($tags as $tag) { unset($this->tags[$tag][$key]); }
                return true;
            }
            public function invalidateTags(array $tags): bool {
                foreach ($tags as $tag) {
                    if (!isset($this->tags[$tag])) continue;
                    foreach (array_keys($this->tags[$tag]) as $key) { unset($this->store[$key]); }
                    unset($this->tags[$tag]);
                }
                return true;
            }
            public function userKey(int $userId, string $suffix): string { return "user_{$userId}_{$suffix}"; }
            public function globalKey(string $suffix): string { return "global_{$suffix}"; }
            public function increment(string $key, int $amount): bool {
                $current = $this->store[$key]['value'] ?? 0;
                if (!is_numeric($current)) { $current = 0; }
                $new = (int)$current + $amount;
                $existingTags = $this->store[$key]['tags'] ?? [];
                $this->store[$key] = ['value' => $new, 'tags' => $existingTags];
                return true;
            }
        };
    }

    #[Test]
    public function invalidate_user_cache_flushes_and_recomputes_statistics(): void
    {
        $cache = $this->makeFakeCacheService();
        // Minimal repository using Eloquent to compute aggregates for this feature test
        $repo = new class implements AnalyticsDtoReadRepository {
            public function getUserStats(int $userId): array {
                return [
                    'invoice_count' => \App\Models\Invoice::where('user_id', $userId)->count(),
                    'client_count' => \App\Models\Client::where('user_id', $userId)->count(),
                    'suppliers_count' => \App\Models\Supplier::where('user_id', $userId)->count(),
                    'total_amount' => (float) \App\Models\Invoice::where('user_id', $userId)->sum('payment_amount'),
                ];
            }
            public function getMonthlyStats(int $userId, int $months): array { return []; }
            public function getClientsWithTotals(int $userId): array { return []; }
        };
        // Instantiate service directly to eliminate container timing side-effects
        $service = new DashboardService($cache, $repo);
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'payment_amount' => 100.00,
            'issue_date' => now()->subMonth(),
        ]);
        // Warm cache (stats only for isolation)
    $stats1 = $service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertEquals(1, $stats1->invoiceCount);
    // Add new invoice (cached value should remain stale until invalidation)
        Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'payment_amount' => 50.00,
            'issue_date' => now()->subDays(5),
        ]);
    $statsCached = $service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertEquals(1, $statsCached->invoiceCount, 'Cached value should remain stale before invalidation.');

    // Invalidate & recompute
    $service->invalidateUserCache($user->id);
        $statsFresh = $service->getUserStatistics(UserId::fromInt($user->id));
        $this->assertEquals(2, $statsFresh->invoiceCount, 'Fresh value should reflect new invoice after invalidation.');
    }
}
