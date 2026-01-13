<?php

namespace App\Application\User\Contracts;

use Illuminate\Support\Collection;

interface UELSApplicationServiceInterface
{
    /**
     * Proxy to Domain UniversalLimitServiceInterface::getEntityLimitInfo
     * @return array
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::canUserCreateEntityWithPeriodType
     * @return bool
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::getUsageStatistics
     * @return array
     */
    public function getUsageStatistics(?int $userId, string $entityType, string $metricType = 'count', string $periodType = 'daily'): array;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::getBestPeriodType
     */
    public function getBestPeriodType(?int $userId, string $entityType, string $metricType = 'count'): string;

    /**
     * Proxy to Domain PermissionLimitResolverInterface::getUserPermissionLimits
     */
    public function getUserPermissionLimits(?int $userId, string $entityType): Collection;
}
