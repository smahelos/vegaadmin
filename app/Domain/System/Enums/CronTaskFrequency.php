<?php

declare(strict_types=1);

namespace App\Domain\System\Enums;

/**
 * The value is stored in `cron_tasks.frequency`. Removing or renaming a case needs a migration that updates its rows,
 * because a task the enum does not offer can be neither scheduled nor run.
 */
enum CronTaskFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case CUSTOM = 'custom';
}
