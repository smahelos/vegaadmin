<?php

declare(strict_types=1);

use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Application\System\Commands\RecordCronTaskRun;
use App\Application\System\Queries\ListActiveCronTasks;
use App\Domain\System\Enums\CronTaskFrequency;
use App\Domain\System\Exceptions\InvalidCommandOptionsException;
use App\Domain\System\ValueObjects\CommandOptions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Every scheduled command, the invoice reminders included, is a cron task managed in the Backpack admin
try {
    if (Schema::hasTable('cron_tasks')) {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $cronTasks = $useCases->run(
            Actor::system(),
            new ListActiveCronTasks,
        );

        foreach ($cronTasks as $task) {
            // Invalid options skip only this task
            try {
                $options = CommandOptions::fromString($task->parameters);
            } catch (InvalidCommandOptionsException $e) {
                Log::error(
                    'Cron task cannot be scheduled',
                    ['cronTaskId' => $task->id->value, 'exception' => $e],
                );

                continue;
            }

            // The options are validated, so passing them unescaped to the shell is safe
            $event = Schedule::command(
                $task->command->value,
                $options->options,
            );

            $time = $task->runAt?->format('H:i');
            switch ($task->frequency) {
                case CronTaskFrequency::CUSTOM:
                    if ($task->customExpression !== null && $task->customExpression !== '') {
                        $event->cron($task->customExpression);
                    } else {
                        $event->daily();
                    }
                    break;
                case CronTaskFrequency::DAILY:
                    if ($time !== null) {
                        $event->dailyAt($time);
                    } else {
                        $event->daily();
                    }
                    break;
                case CronTaskFrequency::WEEKLY:
                    if ($task->dayOfWeek !== null && $time !== null) {
                        $event->weeklyOn($task->dayOfWeek, $time);
                    } else {
                        $event->weekly();
                    }
                    break;
                case CronTaskFrequency::MONTHLY:
                    if ($task->dayOfMonth !== null && $time !== null) {
                        $event->monthlyOn($task->dayOfMonth, $time);
                    } else {
                        $event->monthly();
                    }
                    break;
            }

            // Each task writes its output to its own log
            $logPath = storage_path("logs/cron-{$task->id->value}.log");
            $event->appendOutputTo($logPath);

            // Store the time of the last run and its output
            $event->after(
                function () use ($task, $logPath, $useCases): void {
                    $output = null;
                    if (file_exists($logPath)) {
                        $output = file_get_contents($logPath);
                    }
                    $useCases->run(
                        Actor::system(),
                        new RecordCronTaskRun($task->id, $output),
                    );
                },
            );
        }
    }
} catch (Exception|ValueError $e) {
    // A stored command or frequency that the enums no longer offer (ValueError) stops loading as well
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
