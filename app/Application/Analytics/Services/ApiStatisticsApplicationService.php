<?php

declare(strict_types=1);

namespace App\Application\Analytics\Services;

use App\Application\Analytics\Contracts\ApiStatisticsApplicationServiceInterface;
use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\Shared\Time\Contracts\ClockInterface;
use App\Domain\User\ValueObjects\UserId;

final class ApiStatisticsApplicationService implements ApiStatisticsApplicationServiceInterface
{
    public function __construct(
        private readonly ApiStatisticsRepository $statistics,
        private readonly ClockInterface $clock,
    ) {}

    public function monthlyRevenue(UserId $userId, StatisticsFilter $filter): array
    {
        return $this->statistics->monthlyRevenue(
            $userId,
            $filter->start($this->clock->now()),
            $filter->to,
            $filter->clientIds,
        );
    }

    public function clientRevenue(UserId $userId, StatisticsFilter $filter): array
    {
        return $this->statistics->clientRevenue(
            $userId,
            $filter->start($this->clock->now()),
            $filter->to,
            $filter->clientIds,
        );
    }

    public function invoiceStatus(UserId $userId, StatisticsFilter $filter): array
    {
        return $this->statistics->invoiceStatus(
            $userId,
            $filter->start($this->clock->now()),
            $filter->to,
            $filter->clientIds,
        );
    }

    public function paymentMethods(UserId $userId, StatisticsFilter $filter): array
    {
        return $this->statistics->paymentMethods(
            $userId,
            $filter->start($this->clock->now()),
            $filter->to,
            $filter->clientIds,
        );
    }

    public function revenueExpenses(UserId $userId, StatisticsFilter $filter): array
    {
        $from = $filter->start($this->clock->now());
        $revenue = $this->statistics->revenue(
            $userId,
            $from,
            $filter->to,
            $filter->clientIds,
        );
        $expenses = $this->statistics->expenses(
            $userId,
            $from,
            $filter->to,
        );

        $rows = array_merge($revenue, $expenses);
        usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['month'], (string) $b['month']));

        return $rows;
    }
}
