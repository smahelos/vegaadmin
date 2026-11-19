<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

/**
 * Activity of every user by the invoices of the last 30 days, with the most active users first.
 */
final readonly class UserActivityStatisticsDTO
{
    /**
     * @param  list<UserActivityDTO>  $mostActive
     */
    public function __construct(
        public int $userCount,
        public int $activeUserCount,
        public int $highActivityUserCount,
        public int $inactiveUserCount,
        public array $mostActive,
    ) {}
}
