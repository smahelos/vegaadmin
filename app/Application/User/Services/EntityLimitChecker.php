<?php

namespace App\Application\User\Services;

use App\Domain\User\Contracts\UniversalLimitServiceInterface;

/**
 * Application service that exposes simple API for checking entity limits
 * for the current user and retrieving usage statistics.
 */
class EntityLimitChecker
{
    public function __construct(private readonly UniversalLimitServiceInterface $limits)
    {
    }

    /**
     * Check if user can create the entity.
     *
     * @param int|null $userId
     * @param string $entityType
     * @param string $limitType
     * @param string $periodType
     * @param int|float $value
     * @return array
     */
    public function check(?int $userId, string $entityType, string $limitType = 'count', string $periodType = 'monthly', int|float $value = 1): array
    {
        return $this->limits->checkLimit($userId, $entityType, $limitType, $periodType, $value);
    }

    /**
     * Get usage statistics for user & entity combination.
     *
     * @param int|null $userId
     * @param string $entityType
     * @param string $limitType
     * @param string $periodType
     * @return array|null
     */
    public function stats(?int $userId, string $entityType, string $limitType = 'count', string $periodType = 'monthly'): ?array
    {
        return $this->limits->getUsageStatistics($userId, $entityType, $limitType, $periodType);
    }
}
