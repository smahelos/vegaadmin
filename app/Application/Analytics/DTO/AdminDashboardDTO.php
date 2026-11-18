<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTO;

use App\Domain\Analytics\DTO\PlatformStatisticsDTO;
use App\Domain\Analytics\DTO\UserActivityStatisticsDTO;

final readonly class AdminDashboardDTO
{
    public function __construct(
        public PlatformStatisticsDTO $statistics,
        public UserActivityStatisticsDTO $activity,
    ) {}
}
