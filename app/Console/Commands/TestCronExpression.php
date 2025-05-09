<?php

namespace App\Console\Commands;

use App\Models\CronTask;
use Carbon\Carbon;
use Cron\CronExpression as CronParser;
use Illuminate\Console\Command;

class TestCronExpression extends Command
{
    protected $signature = 'cron:test {id? : tasd ID to test} {--expression= : CRON expression to test}';
    protected $description = 'Tests cron expression and shows time of next run.';

    public function handle()
    {
        if ($this->argument('id')) {
            // Check existing tasks
            $task = CronTask::where('id', $this->argument('id'))->firstOrFail();
            $expression = $task->frequency === 'custom' ? $task->custom_expression : $this->convertFrequencyToExpression($task);
            
            $this->info("Testing of task: {$task->name}");
            $this->info("CRON expression: {$expression}");
        } elseif ($this->option('expression')) {
            // Check custom expression
            $expression = $this->option('expression');
            $this->info("CRON expression testing: {$expression}");
        } else {
            $this->error('You have to set task ID or CRON expression.');
            return 1;
        }

        try {
            $cron = new CronParser($expression);
            $now = Carbon::now();
            
            // Next 5 runs
            $this->info("Next runs (5):");
            
            for ($i = 0; $i < 5; $i++) {
                $nextRun = Carbon::createFromFormat('Y-m-d H:i:s', $cron->getNextRunDate()->format('Y-m-d H:i:s'));
                $this->info(" - " . $nextRun->format('Y-m-d H:i:s') . " (" . $nextRun->diffForHumans() . ")");
                $cron->getNextRunDate();
            }
            
            // Simulation of task execution
            $this->info("Do yu want to simulate task run? [y/N]");
            if ($this->confirm("Simulate task run?")) {
                if ($this->argument('id')) {
                    // Run the task command
                    $this->info("Task run: {$task->command}");
                    $this->call($task->command);
                } else {
                    $this->warning("Simulation is not accessible for testing of task expression.");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Incorrect CRON expression: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Convert frequency to CRON expression.
     *
     * @param CronTask $task
     * @return string
     */
    private function convertFrequencyToExpression(CronTask $task): string
    {
        $runAt = $task->run_at ? Carbon::parse($task->run_at) : Carbon::parse('00:00');
        $minute = $runAt->format('i');
        $hour = $runAt->format('H');

        return match($task->frequency) {
            'daily' => "{$minute} {$hour} * * *",
            'weekly' => "{$minute} {$hour} * * {$task->day_of_week}",
            'monthly' => "{$minute} {$hour} {$task->day_of_month} * *",
            default => '* * * * *', 
        };
    }
}
