<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Time\Contracts\ClockInterface;

final class GetCustomerRevenueHandler
{
    public function __construct(
        private readonly ApiStatisticsRepository $statistics,
        private readonly ClockInterface $clock,
    ) {}

    /** @return list<array<string, mixed>> */
    public function handle(GetCustomerRevenue $query, ReadScope $scope): array
    {
        $filter = $query->filter;

        return $this->statistics->customerRevenue(
            $scope,
            $filter->start($this->clock->now()),
            $filter->to,
            $filter->contactIds,
        );
    }
}
