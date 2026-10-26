<?php

namespace App\Application\User\Contracts;

interface UELSApplicationServiceInterface
{
    /**
     * Proxy to Domain UniversalLimitServiceInterface::getEntityLimitInfo
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::canUserCreateEntityWithPeriodType
     */
    public function canUserCreateEntity(?int $userId, string $entityType): bool;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::getUsageStatistics
     */
    public function getUsageStatistics(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
    ): array;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::getBestPeriodType
     */
    public function getBestPeriodType(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
    ): string;
}
