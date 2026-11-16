<?php

declare(strict_types=1);

namespace App\Application\Analytics\Queries;

use App\Application\Analytics\DTO\DashboardDTO;
use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;

final class GetDashboardHandler
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly MoneyFormatterInterface $money,
    ) {}

    public function handle(GetDashboard $query, ReadScope $scope): DashboardDTO
    {
        $statistics = $this->dashboard->getUserStatistics($scope);

        return new DashboardDTO(
            $statistics,
            array_values(
                array_filter(
                    $this->dashboard->getCustomersWithInvoiceTotals($scope),
                    static fn (CustomerTotalDTO $customer): bool => $customer->contactId !== null,
                ),
            ),
            $this->money->formatWithCode($statistics->totalAmount),
        );
    }
}
