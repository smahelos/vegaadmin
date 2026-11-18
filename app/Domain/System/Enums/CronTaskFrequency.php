<?php

declare(strict_types=1);

namespace App\Domain\System\Enums;

enum CronTaskFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case CUSTOM = 'custom';
}
