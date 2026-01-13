<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SubscriptionPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for subscription plans and subscriptions admin management
        Permission::firstOrCreate([
            'name' => 'can_create_edit_subscription_plan',
            'guard_name' => 'backpack'
        ]);

        Permission::firstOrCreate([
            'name' => 'can_create_edit_subscription',
            'guard_name' => 'backpack'
        ]);

        // Frontend permissions for subscription management
        Permission::firstOrCreate([
            'name' => 'frontend.can_view_subscription',
            'guard_name' => 'web'
        ]);

        Permission::firstOrCreate([
            'name' => 'frontend.can_create_subscription',
            'guard_name' => 'web'
        ]);

        Permission::firstOrCreate([
            'name' => 'frontend.can_cancel_subscription',
            'guard_name' => 'web'
        ]);
    }
}
