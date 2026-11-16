<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Analytics;

use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ReadsDashboard;
use Tests\TestCase;

final class AnalyticsCacheInvalidationTest extends TestCase
{
    use ReadsDashboard;

    /** Simple in-memory tagged cache fake for deterministic behavior. */
    private array $store = [];

    private array $tagIndex = [];

    private function makeFakeCacheService(): CacheServiceInterface
    {
        $storeRef = &$this->store; // Shared store by reference for determinism
        $tagRef = &$this->tagIndex; // Shared tag index for invalidation

        return new class($storeRef, $tagRef) implements CacheServiceInterface
        {
            public function __construct(private array &$store, private array &$tags) {}

            public function remember(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): mixed {
                if (array_key_exists($key, $this->store)) {
                    return $this->store[$key]['value'];
                }
                $value = is_callable($data) ? $data() : $data;
                $this->store[$key] = ['value' => $value, 'tags' => $tags];
                foreach ($tags as $tag) {
                    $this->tags[$tag][$key] = true;
                }

                return $value;
            }

            public function get(string $key): mixed
            {
                return $this->store[$key]['value'] ?? null;
            }

            public function put(
                string $key,
                mixed $data,
                int $ttl = 3600,
                array $tags = [],
            ): bool {
                $this->store[$key] = ['value' => $data, 'tags' => $tags];
                foreach ($tags as $tag) {
                    $this->tags[$tag][$key] = true;
                }

                return true;
            }

            public function forget(string $key): bool
            {
                if (!isset($this->store[$key])) {
                    return false;
                }
                $tags = $this->store[$key]['tags'];
                unset($this->store[$key]);
                foreach ($tags as $tag) {
                    unset($this->tags[$tag][$key]);
                }

                return true;
            }

            public function invalidateTags(array $tags): bool
            {
                foreach ($tags as $tag) {
                    if (!isset($this->tags[$tag])) {
                        continue;
                    }
                    foreach (array_keys($this->tags[$tag]) as $key) {
                        unset($this->store[$key]);
                    }
                    unset($this->tags[$tag]);
                }

                return true;
            }

            public function userKey(int $userId, string $suffix): string
            {
                return "user_{$userId}_{$suffix}";
            }

            public function globalKey(string $suffix): string
            {
                return "global_{$suffix}";
            }

            public function increment(string $key, int $amount): bool
            {
                $current = $this->store[$key]['value'] ?? 0;
                if (!is_numeric($current)) {
                    $current = 0;
                }
                $new = (int) $current + $amount;
                $existingTags = $this->store[$key]['tags'] ?? [];
                $this->store[$key] = ['value' => $new, 'tags' => $existingTags];

                return true;
            }
        };
    }

    #[Test]
    public function invalidate_user_cache_flushes_and_recomputes_statistics(): void
    {
        $application = app();
        $application->instance(
            CacheServiceInterface::class,
            $this->makeFakeCacheService(),
        );
        $service = app(DashboardService::class);
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->assignRole('frontend_user');
        $owner = UserId::fromInt($user->id);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $user->id]);
        $invoiceFactory = Invoice::factory();
        $now = CarbonImmutable::now();
        $invoiceFactory->create(
            [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'payment_amount' => 100.00,
                'issue_date' => $now->subMonth(),
            ],
        );
        // Warm cache (stats only for isolation)
        $stats1 = $this->dashboardOf($owner)->statistics;
        $this->assertEquals(1, $stats1->invoiceCount);
        // Add new invoice without its invalidating event: the cached value stays stale until invalidation
        Invoice::withoutEvents(
            fn () => $invoiceFactory->create(
                [
                    'user_id' => $user->id,
                    'contact_id' => $contact->id,
                    'payment_amount' => 50.00,
                    'issue_date' => $now->subDays(5),
                ],
            ),
        );
        $statsCached = $this->dashboardOf($owner)->statistics;
        $this->assertEquals(
            1,
            $statsCached->invoiceCount,
            'Cached value should remain stale before invalidation.',
        );

        // Invalidate & recompute
        $service->invalidateUserCache($user->id);
        $statsFresh = $this->dashboardOf($owner)->statistics;
        $this->assertEquals(
            2,
            $statsFresh->invoiceCount,
            'Fresh value should reflect new invoice after invalidation.',
        );
    }
}
