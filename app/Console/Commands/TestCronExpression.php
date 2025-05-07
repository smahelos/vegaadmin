<?php

namespace App\Console\Commands;

use App\Models\CronTask;
use Carbon\Carbon;
use Cron\CronExpression as CronParser;
use Illuminate\Console\Command;

class TestCronExpression extends Command
{
    protected $signature = 'cron:test {id? : ID úlohy pro testování} {--expression= : CRON výraz pro testování}';
    protected $description = 'Otestuje CRON výraz a zobrazí čas příštího spuštění';

    public function handle()
    {
        if ($this->argument('id')) {
            // Test existující úlohy
            $task = CronTask::where('id', $this->argument('id'))->firstOrFail();
            $expression = $task->frequency === 'custom' ? $task->custom_expression : $this->convertFrequencyToExpression($task);
            
            $this->info("Testování úlohy: {$task->name}");
            $this->info("CRON výraz: {$expression}");
        } elseif ($this->option('expression')) {
            // Test zadaného výrazu
            $expression = $this->option('expression');
            $this->info("Testování CRON výrazu: {$expression}");
        } else {
            $this->error('Musíte zadat ID úlohy nebo CRON výraz.');
            return 1;
        }

        try {
            $cron = new CronParser($expression);
            $now = Carbon::now();
            
            // Zobrazení příštích 5 spuštění
            $this->info("Příští spuštění (5):");
            
            for ($i = 0; $i < 5; $i++) {
                $nextRun = Carbon::createFromFormat('Y-m-d H:i:s', $cron->getNextRunDate()->format('Y-m-d H:i:s'));
                $this->info(" - " . $nextRun->format('Y-m-d H:i:s') . " (" . $nextRun->diffForHumans() . ")");
                $cron->getNextRunDate(); // Posun na další datum
            }
            
            // Simulace spuštění
            $this->info("Chcete simulovat spuštění úlohy? [y/N]");
            if ($this->confirm("Simulovat spuštění?")) {
                if ($this->argument('id')) {
                    // Spuštění příkazu z úlohy
                    $this->info("Spouštění: {$task->command}");
                    $this->call($task->command);
                } else {
                    $this->warning("Simulace není dostupná pro testování samostatného výrazu.");
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Neplatný CRON výraz: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Převede frekvenci cron úlohy na standardní CRON výraz.
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
            default => '* * * * *', // Nemělo by nastat
        };
    }
}
