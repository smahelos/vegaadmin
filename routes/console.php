<?php

declare(strict_types=1);

use App\Models\CronTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// The invoice payment check runs whatever the database holds
$event = Schedule::command('invoices:check-payment-status');
$event->dailyAt('09:00');
$event->appendOutputTo(
    storage_path('logs/invoice-reminders.log'),
);

// Tasks managed in the Backpack admin come from the database
try {
    if (Schema::hasTable('cron_tasks')) {
        $query = CronTask::where('is_active', true);
        $cronTasks = $query->get();

        foreach ($cronTasks as $task) {
            $event = Schedule::command($task->command);

            if ($task->frequency === 'custom' && $task->custom_expression) {
                $event->cron($task->custom_expression);
            } else {
                switch ($task->frequency) {
                    case 'daily':
                        if ($task->run_at) {
                            $time = $task->run_at->format('H:i');
                            $event->dailyAt($time);
                        } else {
                            $event->daily();
                        }
                        break;
                    case 'weekly':
                        if ($task->day_of_week !== null && $task->run_at) {
                            $time = $task->run_at->format('H:i');
                            $event->weeklyOn($task->day_of_week, $time);
                        } else {
                            $event->weekly();
                        }
                        break;
                    case 'monthly':
                        if ($task->day_of_month !== null && $task->run_at) {
                            $time = $task->run_at->format('H:i');
                            $event->monthlyOn($task->day_of_month, $time);
                        } else {
                            $event->monthly();
                        }
                        break;
                    default:
                        $event->daily();
                }
            }

            // Each task writes its output to its own log
            $event->appendOutputTo(
                storage_path("logs/cron-{$task->id}.log"),
            );

            // Store the time of the last run and its output
            $event->after(
                function () use ($task): void {
                    $task->last_run = now();
                    $logPath = storage_path("logs/cron-{$task->id}.log");
                    if (file_exists($logPath)) {
                        $task->last_output = file_get_contents($logPath);
                    } else {
                        $task->last_output = null;
                    }
                    $task->save();
                },
            );
        }
    }
} catch (Exception $e) {
    Log::error(
        'Cron task loading failed',
        ['exception' => $e],
    );
}

$command = Artisan::command(
    'inspire',
    function (): void {
        $this->comment(Inspiring::quote());
    },
);
$command->purpose('Display an inspiring quote');
