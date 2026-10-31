<?php

declare(strict_types=1);

namespace App\Application\Analytics\Services;

use App\Application\Analytics\DTO\DashboardDTO;
use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\Services\DashboardService;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\User\ValueObjects\UserId;

final class DashboardApplicationService
{
    public function __construct(private readonly DashboardService $dashboard, private readonly MoneyFormatterInterface $money) {}

    public function getDashboardData(UserId $userId): DashboardDTO
    {
        $statistics = $this->dashboard->getUserStatistics($userId);

        return new DashboardDTO(
            $statistics,
            array_values(array_filter(
                $this->dashboard->getCustomersWithInvoiceTotals($userId),
                static fn (CustomerTotalDTO $customer): bool => $customer->contactId !== null,
            )),
            $this->money->formatWithCode($statistics->totalAmount),
        );
    }
}
