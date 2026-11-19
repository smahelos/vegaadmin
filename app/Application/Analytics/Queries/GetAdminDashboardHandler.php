<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\DTO\AdminDashboardDTO;
use App\Domain\Analytics\Contracts\AnalyticsReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class GetAdminDashboardHandler
{
    private const RECENT_DAYS = 30;

    private const MOST_ACTIVE_USERS = 5;

    public function __construct(
        private readonly AnalyticsReadRepositoryInterface $analytics,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(GetAdminDashboard $query, AllOwnersScope $scope): AdminDashboardDTO
    {
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        return new AdminDashboardDTO(
            $this->analytics->getPlatformStatistics(
                $scope,
                $now->modify('-' . self::RECENT_DAYS . ' days'),
                $today->modify('first day of this month'),
            ),
            $this->analytics->getUserActivity($scope, self::MOST_ACTIVE_USERS),
        );
    }
}
