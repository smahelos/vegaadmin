<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTO;

use App\Domain\Analytics\DTO\CustomerTotalDTO;
use App\Domain\Analytics\DTO\UserStatisticsDTO;

final readonly class DashboardDTO
{
    /** @param list<CustomerTotalDTO> $contacts */
    public function __construct(
        public UserStatisticsDTO $statistics,
        public array $contacts,
        public string $totalAmountFormatted,
    ) {}
}
