<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Domain\User\Contracts\UniversalLimitServiceInterface;
use App\Domain\User\Services\PermissionLimitResolver;
use App\Http\Controllers\Api\UELSController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UELSControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    private UniversalLimitServiceInterface $fakeService;
    private PermissionLimitResolver $fakeResolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Test-only routes bypassing complex middleware chains to reach controller logic deterministically
        Route::middleware(['web','auth:web'])->group(function() {
            Route::get('/test/uels/{entity}', [UELSController::class, 'getEntityData']);
            Route::get('/test/uels-all', [UELSController::class, 'getAllEntitiesData']);
            Route::get('/test/uels-permissions', [UELSController::class, 'getPermissions']);
        });

        // Bind fake PermissionLimitResolver as singleton with configurable permission limits
        $this->app->singleton(PermissionLimitResolver::class, function () {
            return new class extends PermissionLimitResolver {
                public array $permissionLimits = [
                    'client' => [], 'supplier' => [], 'product' => [], 'invoice' => []
                ];
                public function __construct() {
                    $limitRepo = new class implements \App\Domain\User\Contracts\EntityLimitRepositoryInterface {
                        public function getActiveByPermissions(array $permissionNames): array { return []; }
                        public function getPermissionNamesForEntity(string $entityType, bool $includeInactive = true): array { return []; }
                        public function getActiveByPermissionsFiltered(array $permissionNames, string $entityType, string $metricType, string $periodType): array { return []; }
                        public function getAllEntityTypes(): array { return ['client','supplier','product','invoice']; }
                        public function getAllMetricTypes(): array { return ['count' => 'count']; }
                        public function getAllPeriodTypes(): array { return ['daily' => 'daily','weekly' => 'weekly','monthly' => 'monthly','yearly' => 'yearly','lifetime' => 'lifetime']; }
                    };
                    $permissionSvc = new class implements \App\Domain\User\Contracts\UserPermissionServiceInterface {
                        public function userExists(int $userId): bool { return true; }
                        public function getUserPermissions(int $userId, string $guard = 'web'): array { return []; }
                        public function hasPermission(int $userId, string $permission, string $guard = 'web'): bool { return false; }
                        public function hasAnyPermission(int $userId, array $permissions, string $guard = 'web'): bool { return false; }
                    };
                    $cache = new class implements \App\Domain\Shared\Cache\Contracts\CacheServiceInterface {
                        private array $data = [];
                        public function remember(string $key, mixed $data, int $ttl = 3600, array $tags = []): mixed { return is_callable($data) ? $this->data[$key] = $data() : ($this->data[$key] = $data); }
                        public function get(string $key): mixed { return $this->data[$key] ?? null; }
                        public function put(string $key, mixed $data, int $ttl = 3600, array $tags = []): bool { $this->data[$key] = $data; return true; }
                        public function forget(string $key): bool { unset($this->data[$key]); return true; }
                        public function invalidateTags(array $tags): bool { return true; }
                        public function userKey(int $userId, string $suffix): string { return "u_{$userId}_{$suffix}"; }
                        public function globalKey(string $suffix): string { return "g_{$suffix}"; }
                        public function increment(string $key, int $amount): bool { $current = (int)($this->data[$key] ?? 0); $this->data[$key] = $current + $amount; return true; }
                    };
                    parent::__construct($limitRepo, $permissionSvc, $cache);
                }
                public function getUserLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): int { return 0; }
                public function getAllUserLimits(?int $userId): array { return []; }
                public function getEntityLimits(?int $userId, string $entityType): array { return []; }
                public function getUserPermissionLimits(?int $userId, string $entity): array { return $this->permissionLimits[$entity] ?? []; }
            };
        });

        // Bind fake UniversalLimitServiceInterface used by controller
        $this->app->singleton(UniversalLimitServiceInterface::class, function () {
            $svc = new class implements UniversalLimitServiceInterface {
                public array $entityInfo = [
                    'has_permission' => true,
                    'user_permissions' => null,
                    'limits' => []
                ];
                public array $usageStats = [];
                public function checkLimit(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily', int|float $value = 1): array { return []; }
                public function recordUsage(?int $userId, string $entityType, string $metricType = 'count', ?string $periodType = 'daily', string $guard = 'web', int|float $value = 1): bool { return true; }
                public function resetUsage(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): bool { return true; }
                public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array {
                    return $this->usageStats[$periodType] ?? [];
                }
                public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string { return 'monthly'; }
                public function getEntityLimitInfo(?int $userId, string $entityType): array {
                    if ($this->entityInfo['user_permissions'] === null) {
                        $this->entityInfo['user_permissions'] = [[ 'daily' => true, 'weekly' => true, 'monthly' => true, 'yearly' => true, 'lifetime' => true ]];
                    }
                    return $this->entityInfo;
                }
                public function canUserCreateEntity(?int $userId, string $entityType): bool {
                    return (bool)($this->entityInfo['has_permission'] ?? false);
                }
            };
            // Return stub instance
            return $svc;
        });
    // Resolve now so we hold references
        $this->fakeService = $this->app->make(UniversalLimitServiceInterface::class);
    $this->fakeResolver = $this->app->make(PermissionLimitResolver::class);
    }

    private function makeUser(): User
    {
        return User::factory()->create([ 'password' => Hash::make('secret123') ]);
    }

    #[Test]
    public function entity_data_requires_authentication(): void
    {
        $resp = $this->get('/test/uels/client');
        // For unauthenticated web route expect 302 redirect to login
        $resp->assertStatus(302);
    }

    #[Test]
    public function entity_data_invalid_entity_returns_400(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $resp = $this->getJson('/test/uels/invalid');
        $resp->assertStatus(400)->assertJson(['error' => 'Invalid entity type']);
    }

    #[Test]
    public function entity_data_no_permission_returns_no_permission_payload(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $this->fakeService->entityInfo = [
            'has_permission' => false,
            'user_permissions' => [],
            'limits' => []
        ];
        $resp = $this->getJson('/test/uels/client');
        $resp->assertOk();
        $resp->assertJsonPath('status', 'no_permission');
        $this->assertTrue($resp->json('is_at_limit'));
    }

    #[Test]
    public function entity_data_unlimited_when_no_stats(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $this->fakeService->entityInfo = [
            'has_permission' => true,
            'user_permissions' => [[ 'daily' => false ]],
            'limits' => []
        ];
        $this->fakeService->usageStats = []; // no stats generated
        $resp = $this->getJson('/test/uels/client');
        $resp->assertOk();
        $this->assertEquals('unlimited', $resp->json('status'));
        $this->assertEquals(-1, $resp->json('limit'));
    }

    #[Test]
    public function entity_data_selects_highest_effective_limit_period(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        // Setup stats: weekly wins (limit 10 -> effective 42) vs daily 1 -> 30, monthly 15 -> 15
        $this->fakeService->entityInfo = [
            'has_permission' => true,
            'user_permissions' => [[ 'daily' => true, 'weekly' => true, 'monthly' => true ]],
            'limits' => []
        ];
        $this->fakeService->usageStats = [
            'daily' => [ 'limit' => 1, 'current_usage' => 0, 'remaining' => 1, 'usage_percentage' => 0, 'can_create' => true, 'period_start' => 's','period_end' => 'e' ],
            'weekly' => [ 'limit' => 10, 'current_usage' => 2, 'remaining' => 8, 'usage_percentage' => 20, 'can_create' => true, 'period_start' => 's','period_end' => 'e' ],
            'monthly' => [ 'limit' => 15, 'current_usage' => 5, 'remaining' => 10, 'usage_percentage' => 33.3, 'can_create' => true, 'period_start' => 's','period_end' => 'e' ],
            'yearly' => [],
            'lifetime' => []
        ];
        $resp = $this->getJson('/test/uels/client');
        $resp->assertOk();
        $this->assertEquals(10, $resp->json('limit')); // weekly chosen
        $this->assertEquals(2, $resp->json('current_usage'));
        $this->assertEquals('success', $resp->json('status'));
    }

    #[Test]
    public function all_entities_endpoint_aggregates_basic_structure(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        $this->fakeService->entityInfo = [
            'has_permission' => true,
            'user_permissions' => [[ 'daily' => true ]],
            'limits' => []
        ];
        $this->fakeService->usageStats = [
            'daily' => [ 'limit' => 3,'current_usage' => 1,'remaining' => 2,'usage_percentage' => 33,'can_create' => true,'period_start' => 's','period_end' => 'e' ]
        ];
    $resp = $this->getJson('/test/uels-all');
        $resp->assertOk()->assertJsonStructure(['entities' => ['client','supplier','product','invoice']]);
    }

    #[Test]
    public function permissions_endpoint_requires_authentication(): void
    {
        $resp = $this->get('/test/uels-permissions');
        $resp->assertStatus(302);
    }

    #[Test]
    public function permissions_endpoint_returns_empty_when_no_limits(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        // leave permissionLimits empty
        $resp = $this->getJson('/test/uels-permissions');
        $resp->assertOk();
        $this->assertEquals([], $resp->json('permission_limits'));
    }

    #[Test]
    public function permissions_endpoint_returns_configured_limits(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'web');
        // Configure limits for client & product directly on anonymous instance
        $resolver = $this->fakeResolver;
        $setLimits = function(object $resolver, string $entity, array $limits): void {
            if (property_exists($resolver, 'permissionLimits')) {
                $resolver->permissionLimits[$entity] = $limits;
            }
        };
        $setLimits($resolver, 'client', [
            ['permission' => 'client.create.daily.10', 'limit' => 10, 'period' => 'daily', 'metric' => 'count']
        ]);
        $setLimits($resolver, 'product', [
            ['permission' => 'product.create.monthly.50', 'limit' => 50, 'period' => 'monthly', 'metric' => 'count']
        ]);
        $resp = $this->getJson('/test/uels-permissions');
        $resp->assertOk();
        $this->assertArrayHasKey('client', $resp->json('permission_limits'));
        $this->assertArrayHasKey('product', $resp->json('permission_limits'));
        $this->assertEquals(10, $resp->json('permission_limits.client.0.limit'));
        $this->assertEquals(50, $resp->json('permission_limits.product.0.limit'));
    }
}
