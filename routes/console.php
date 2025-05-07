<?php

use App\Models\CronTask;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Schedule;


// Explicitně registrujeme základní příkaz pro kontrolu faktur
Schedule::command('invoices:check-payment-status')
    ->dailyAt('09:00')
    ->appendOutputTo(storage_path('logs/invoice-reminders.log'));

// Dynamické načítání úloh z databáze (pro Backpack rozhraní)
try {
    if (Schema::hasTable('cron_tasks')) {
        $cronTasks = CronTask::where('is_active', true)->get();
        
        foreach ($cronTasks as $task) {
            $event = Schedule::command($task->command);
            
            if ($task->frequency === 'custom' && $task->custom_expression) {
                $event->cron($task->custom_expression);
            } else {
                switch ($task->frequency) {
                    case 'daily':
                        if ($task->run_at) {
                            $time = is_string($task->run_at) ? $task->run_at : Carbon\Carbon::parse($task->run_at)->format('H:i');
                            $event->dailyAt($time);
                        } else {
                            $event->daily();
                        }
                        break;
                    case 'weekly':
                        if ($task->day_of_week !== null && $task->run_at) {
                            $time = is_string($task->run_at) ? $task->run_at : Carbon\Carbon::parse($task->run_at)->format('H:i');
                            $event->weeklyOn($task->day_of_week, $time);
                        } else {
                            $event->weekly();
                        }
                        break;
                    case 'monthly':
                        if ($task->day_of_month !== null && $task->run_at) {
                            $time = is_string($task->run_at) ? $task->run_at : Carbon\Carbon::parse($task->run_at)->format('H:i');
                            $event->monthlyOn($task->day_of_month, $time);
                        } else {
                            $event->monthly();
                        }
                        break;
                    default:
                        $event->daily();
                }
            }
            
            // Zapisovat výstup do logů
            $event->appendOutputTo(storage_path("logs/cron-{$task->id}.log"));
            
            // Aktualizovat čas posledního běhu a výstup
            $event->after(function () use ($task) {
                $task->last_run = now();
                $task->last_output = file_exists(storage_path("logs/cron-{$task->id}.log")) 
                    ? file_get_contents(storage_path("logs/cron-{$task->id}.log")) 
                    : null;
                $task->save();
            });
        }
    }
} catch (\Exception $e) {
    \Log::error("Chyba při načítání cron úloh: " . $e->getMessage());
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
