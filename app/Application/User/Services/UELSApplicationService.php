<?php

namespace App\Application\User\Services;

use App\Application\User\Contracts\UELSApplicationServiceInterface;
use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;
use App\Domain\User\Contracts\PermissionLimitResolverInterface as PermissionLimitResolverInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;
use Illuminate\Support\Collection;

class UELSApplicationService implements UELSApplicationServiceInterface
{
    public function __construct(
        private readonly UniversalLimitService $limitService,
        private readonly PermissionLimitResolverInterface $permissionResolver,
    ) {
    }

    public function getEntityLimitInfo(?int $userId, string $entityType): array
    {
        return $this->limitService->getEntityLimitInfo($userId, $entityType);
    }

    public function canUserCreateEntity(?int $userId, string $entityType): bool
    {
        return $this->limitService->canUserCreateEntity($userId, $entityType);
    }

    public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array
    {
        return $this->limitService->getUsageStatistics($userId, $entityType, $metricType, $periodType);
    }

    public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string
    {
        return $this->limitService->getBestPeriodType($userId, $entityType, $metricType);
    }

    public function getUserPermissionLimits(?int $userId, string $entityType): Collection
    {
        // Domain returns array; adapt to Collection for controllers/consumers in Application layer
        return collect($this->permissionResolver->getUserPermissionLimits($userId, $entityType));
    }
}
