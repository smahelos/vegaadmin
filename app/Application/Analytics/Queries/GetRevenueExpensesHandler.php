<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class GetRevenueExpensesHandler
{
    public function __construct(
        private readonly ApiStatisticsRepository $statistics,
        private readonly ClockInterface $clock,
    ) {}

    /** @return list<array<string, mixed>> */
    public function handle(GetRevenueExpenses $query, ReadScope $scope): array
    {
        $filter = $query->filter;
        $from = $filter->start($this->clock->now());
        $revenue = $this->statistics->revenue(
            $scope,
            $from,
            $filter->to,
            $filter->contactIds,
        );
        $expenses = $this->statistics->expenses(
            $scope,
            $from,
            $filter->to,
        );

        $rows = array_merge($revenue, $expenses);
        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp((string) $a['month'], (string) $b['month']),
        );

        return $rows;
    }
}
