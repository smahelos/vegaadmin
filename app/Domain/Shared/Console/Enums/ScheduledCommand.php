<?php

declare(strict_types=1);

namespace App\Domain\Shared\Console\Enums;

/**
 * The Artisan commands a cron task may run. The value is the command name, stored in `cron_tasks.command`; any
 * other command, including those of Laravel and the packages, cannot be scheduled from the admin.
 */
enum ScheduledCommand: string
{
    case CHECK_INVOICE_PAYMENT_STATUS = 'invoices:check-payment-status';
    case OPTIMIZE_DATABASE = 'db:optimize';
    case CHECK_DATABASE_HEALTH = 'db:health-check';
    case MONITOR_DATABASE = 'db:monitor';
    case CLEAN_PERFORMANCE_METRICS = 'db:clean-metrics';
}
