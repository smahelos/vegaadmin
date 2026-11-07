<?php

namespace App\Application\User\Contracts;

interface UELSApplicationServiceInterface
{
    /**
     * Proxy to Domain UniversalLimitServiceInterface::getEntityLimitInfo
     */
    public function getEntityLimitInfo(?int $userId, string $entityType): array;

    /**
     * Proxy to Domain UniversalLimitServiceInterface::getUsageStatistics
     */
    public function getUsageStatistics(
        ?int $userId,
        string $entityType,
        string $metricType = 'count',
        string $periodType = 'daily',
    ): array;
}
