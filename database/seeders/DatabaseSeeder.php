<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Reference data for every environment (the test suite included), development accounts and
     * their data in local.
     */
    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);
        $this->call(CronTasksSeeder::class);
        $this->call(PageContentSeeder::class);
        $this->call(SubscriptionPlanSeeder::class);

        if (app()->environment('local')) {
            $this->call(DevelopmentDataSeeder::class);
        }
    }
}
