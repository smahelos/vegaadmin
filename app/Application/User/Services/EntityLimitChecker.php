<?php

namespace App\Application\User\Services;

use App\Domain\User\Contracts\UniversalLimitServiceInterface;

/**
 * Application service that exposes simple API for checking entity limits
 * for the current user and retrieving usage statistics.
 */
class EntityLimitChecker
{
    public function __construct(private readonly UniversalLimitServiceInterface $limits) {}

    /**
     * Check if user can create the entity.
     */
    public function check(
        ?int $userId,
        string $entityType,
        string $limitType = 'count',
        string $periodType = 'monthly',
        int|float $value = 1,
    ): array {
        return $this->limits->checkLimit(
            $userId,
            $entityType,
            $limitType,
            $periodType,
            $value,
        );
    }
}
