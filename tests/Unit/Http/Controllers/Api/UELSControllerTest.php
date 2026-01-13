<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\UELSController;
use App\Application\User\Contracts\UELSApplicationServiceInterface;
use Illuminate\Http\Request as HttpRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UELSControllerTest extends TestCase
{
    private ServiceStub $service;
    private UELSController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ServiceStub();
        // Inject only the application service as per current controller signature
        $this->controller = new UELSController($this->service);
    }

    private function makeRequest(): HttpRequest
    {
        $req = HttpRequest::create('/api/uels/client/data','GET');
        // Anonymous user -> null, keeps unit test free of Eloquent models
        $req->setUserResolver(fn() => null);
        return $req;
    }

    #[Test]
    public function invalid_entity_returns_400(): void
    {
        $resp = $this->controller->getEntityData($this->makeRequest(), 'invalid');
        $this->assertEquals(400, $resp->status());
        $this->assertEquals('Invalid entity type', $resp->getData(true)['error']);
    }

    #[Test]
    public function no_permission_entity_returns_expected_payload(): void
    {
        $this->service->entityInfo = [
            'has_permission' => false,
            'user_permissions' => collect(),
            'limits' => []
        ];
        $resp = $this->controller->getEntityData($this->makeRequest(), 'client');
        $data = $resp->getData(true);
        $this->assertEquals('no_permission', $data['status']);
        $this->assertTrue($data['is_at_limit']);
        $this->assertEquals(0, $data['limit']);
    }

    #[Test]
    public function unlimited_status_when_has_permission_but_no_stats(): void
    {
        $this->service->entityInfo = [
            'has_permission' => true,
            'user_permissions' => collect([[ 'daily' => false ]]),
            'limits' => []
        ];
        $this->service->usageStats = []; // ensures no primary stats
        $resp = $this->controller->getEntityData($this->makeRequest(), 'client');
        $data = $resp->getData(true);
        $this->assertEquals('unlimited', $data['status']);
        $this->assertEquals(-1, $data['limit']);
    }

    #[Test]
    public function selects_best_period_based_on_effective_limit(): void
    {
        $this->service->entityInfo = [
            'has_permission' => true,
            'user_permissions' => collect([[ 'daily' => true, 'weekly' => true, 'monthly' => true ]]),
            'limits' => []
        ];
        $this->service->usageStats = [
            'daily' => [ 'limit' => 2,'current_usage' => 1,'remaining' => 1,'usage_percentage' => 50,'can_create' => true,'period_start' => 's','period_end' => 'e' ],
            'weekly' => [ 'limit' => 5,'current_usage' => 2,'remaining' => 3,'usage_percentage' => 40,'can_create' => true,'period_start' => 's','period_end' => 'e' ],
            'monthly' => [ 'limit' => 10,'current_usage' => 4,'remaining' => 6,'usage_percentage' => 40,'can_create' => true,'period_start' => 's','period_end' => 'e' ],
            'yearly' => [],
            'lifetime' => []
        ];

        $resp = $this->controller->getEntityData($this->makeRequest(), 'client');
        $data = $resp->getData(true);
        $this->assertEquals(2, $data['limit']);
        $this->assertEquals(1, $data['current_usage']);
        $this->assertEquals('success', $data['status']);
    }
}

// Lightweight application service stub (no framework dependencies)
class ServiceStub implements UELSApplicationServiceInterface
{
    public array $entityInfo = [ 'has_permission' => true, 'user_permissions' => null, 'limits' => [] ];
    public array $usageStats = [];

    public function getEntityLimitInfo(?int $userId, string $entityType): array
    {
        if ($this->entityInfo['user_permissions'] === null) {
            $this->entityInfo['user_permissions'] = collect([[ 'daily' => true, 'weekly' => true, 'monthly' => true ]]);
        }
        return $this->entityInfo;
    }

    public function canUserCreateEntity(?int $userId, string $entityType): bool
    {
        return (bool)($this->entityInfo['has_permission'] ?? false);
    }

    public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array
    {
        return $this->usageStats[$periodType] ?? [];
    }

    public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string
    {
        return 'monthly';
    }

    public function getUserPermissionLimits(?int $userId, string $entityType): \Illuminate\Support\Collection
    {
        // Derive permissions from entityInfo structure for simplicity in unit tests
        $perms = $this->entityInfo['user_permissions'] ?? collect();
        return $perms instanceof \Illuminate\Support\Collection ? $perms : collect($perms);
    }
}
