<?php

namespace App\Application\Analytics\Services;

use App\Application\Analytics\Contracts\ClientTotalPresenterInterface;
use App\Application\Analytics\Contracts\DashboardApplicationServiceInterface;
use App\Application\Analytics\Contracts\MonthlyStatPresenterInterface;
use App\Application\Analytics\Contracts\UserStatisticsPresenterInterface;
use App\Domain\Analytics\Contracts\DashboardServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Models\User;

/**
 * DashboardService (Analytics Domain)
 * Responsible for aggregated analytical metrics exposed to UI.
 */
class DashboardApplicationService implements DashboardApplicationServiceInterface
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService,
        private readonly UserStatisticsPresenterInterface $statsPresenter,
        private readonly MonthlyStatPresenterInterface $monthlyPresenter,
        private readonly ClientTotalPresenterInterface $clientPresenter,
    ) {}

    /** {@inheritDoc} */
    public function getDashboardData(User $user): array
    {
        $userId = UserId::fromInt($user->id);
        $stats = $this->dashboardService->getUserStatistics($userId);
        $monthly = $this->dashboardService->getMonthlyStatistics($userId);
        $clients = $this->dashboardService->getClientsWithInvoiceTotals($userId);

        return [
            'statistics' => $stats,
            'statistics_formatted' => $this->statsPresenter->present($stats),
            'monthly_stats' => $monthly,
            'monthly_stats_formatted' => array_map(
                fn ($dto) => $this->monthlyPresenter->present($dto),
                $monthly,
            ),
            'clients' => $clients,
            'clients_formatted' => array_map(
                fn ($dto) => $this->clientPresenter->present($dto),
                $clients,
            ),
        ];
    }
}
