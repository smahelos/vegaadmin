<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Console\Enums\ScheduledCommand;
use App\Models\CronTask;
use Illuminate\Database\Seeder;

/**
 * The invoice reminders run only as this cron task: without it, no reminder is sent.
 */
final class CronTasksSeeder extends Seeder
{
    public function run(): void
    {
        // The command's default options: 3 days before the due date and 1 day after it
        CronTask::firstOrCreate(
            ['command' => ScheduledCommand::CHECK_INVOICE_PAYMENT_STATUS],
            [
                'name' => __('cron_tasks.predefined.payment_reminders.name'),
                'frequency' => 'daily',
                'run_at' => '09:00',
                'is_active' => true,
                'description' => __('cron_tasks.predefined.payment_reminders.description'),
            ],
        );
    }
}
