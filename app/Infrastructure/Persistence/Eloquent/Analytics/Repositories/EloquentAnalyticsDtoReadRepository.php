<?php

namespace App\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Domain\Analytics\Contracts\AnalyticsDtoReadRepository;

class EloquentAnalyticsDtoReadRepository implements AnalyticsDtoReadRepository
{
    public function __construct(
        private readonly EloquentAnalyticsReadRepository $analyticsReadRepository,
    ) {}

    public function getUserStats(int $userId): array
    {
        return $this->analyticsReadRepository->getUserStats($userId);
    }

    public function getMonthlyStats(int $userId, int $months): array
    {
        return $this->analyticsReadRepository->getMonthlyStats($userId, $months);
    }

    public function getClientsWithTotals(int $userId): array
    {
        return $this->analyticsReadRepository->getClientsWithTotals($userId);
    }
}
