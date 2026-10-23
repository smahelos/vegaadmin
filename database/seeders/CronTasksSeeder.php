<?php

namespace Database\Seeders;

use App\Models\CronTask;
use Illuminate\Database\Seeder;

class CronTasksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kontrola faktur před splatností (např. 3 dny dopředu)
        CronTask::create([
            'name' => __('cron_tasks.predefined.upcoming_invoices.name'),
            'command' => 'invoices:check-payment-status --days-before=3 --days-after=1',
            'frequency' => 'daily',
            'run_at' => '09:00',
            'is_active' => true,
            'description' => __('cron_tasks.predefined.upcoming_invoices.description'),
        ]);

        // Kontrola faktur po splatnosti - lze spustit v jinou dobu
        CronTask::create([
            'name' => __('cron_tasks.predefined.overdue_invoices.name'),
            'command' => 'invoices:check-payment-status --days-before=0 --days-after=7',
            'frequency' => 'daily',
            'run_at' => '14:00',
            'is_active' => true,
            'description' => __('cron_tasks.predefined.overdue_invoices.description'),
        ]);

        // Pravidelná kontrola faktur po delší splatnosti - např. každý týden
        CronTask::create([
            'name' => __('cron_tasks.predefined.long_overdue_invoices.name'),
            'command' => 'invoices:check-payment-status --days-before=0 --days-after=14',
            'frequency' => 'weekly',
            'run_at' => '10:00',
            'day_of_week' => 1, // Pondělí
            'is_active' => true,
            'description' => __('cron_tasks.predefined.long_overdue_invoices.description'),
        ]);
    }
}
