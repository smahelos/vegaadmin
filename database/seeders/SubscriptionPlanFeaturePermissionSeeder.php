<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubscriptionPlanFeaturePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permission for subscription plan feature management
        Permission::firstOrCreate([
            'name' => 'can_create_edit_subscription_plan_feature',
            'guard_name' => 'backpack'
        ]);
    }
}
