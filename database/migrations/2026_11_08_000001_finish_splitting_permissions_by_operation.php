<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Each permission the naming standard replaces, with its successors. Roles and users holding the old one get
     * all of them. An empty list marks a permission that no code checks.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const REPLACEMENTS = [
        'backpack' => [
            'backpack.api.extended' => [],
            'backpack.api.invoices' => [],
            'backpack.api.products' => [],
            'can_create_edit_subscription' => [
                'can_view_subscription',
                'can_create_subscription',
                'can_update_subscription',
                'can_delete_subscription',
            ],
            'can_create_edit_subscription_plan' => [
                'can_view_subscription_plan',
                'can_create_subscription_plan',
                'can_update_subscription_plan',
                'can_delete_subscription_plan',
            ],
            'can_create_edit_subscription_plan_feature' => [
                'can_view_subscription_plan_feature',
                'can_create_subscription_plan_feature',
                'can_update_subscription_plan_feature',
                'can_delete_subscription_plan_feature',
            ],
            'can_create_edit_user' => [
                'can_view_user',
                'can_create_user',
                'can_update_user',
                'can_delete_user',
            ],
        ],
        'web' => [
            'frontend.api.extended' => [],
            'frontend.api.invoices' => [],
            'frontend.api.products' => [],
            'frontend.api.statistics' => ['frontend.can_view_statistics'],
            'frontend.can_create_edit_expense' => [],
            'frontend.can_create_edit_product' => [
                'frontend.can_create_product',
                'frontend.can_update_product',
            ],
        ],
    ];

    /**
     * The frontend roles that held each replaced permission by the catalog.
     *
     * @var array<string, list<string>>
     */
    private const FRONTEND_GRANTS = [
        'frontend.api.extended' => ['frontend_user_plus'],
        'frontend.api.invoices' => ['frontend_user', 'frontend_user_plus'],
        'frontend.api.products' => ['frontend_user', 'frontend_user_plus'],
        'frontend.api.statistics' => ['frontend_user', 'frontend_user_plus'],
        'frontend.can_create_edit_expense' => ['frontend_user_plus'],
        'frontend.can_create_edit_product' => ['frontend_user', 'frontend_user_plus'],
    ];

    public function up(): void
    {
        $catalogSync = app(PermissionCatalogSync::class);
        $catalogSync->sync();

        DB::transaction(
            function (): void {
                foreach (self::REPLACEMENTS as $guard => $replacements) {
                    foreach ($replacements as $old => $new) {
                        $permissionQuery = Permission::where('name', $old);
                        $permissionQuery->where('guard_name', $guard);
                        $legacy = $permissionQuery->first();
                        if ($legacy === null) {
                            continue;
                        }

                        // Models, not names: a user resolves a name in its default guard, not the permission's
                        $successorQuery = Permission::whereIn('name', $new);
                        $successorQuery->where('guard_name', $guard);
                        $successors = $successorQuery->get();
                        foreach ($legacy->roles as $role) {
                            $role->givePermissionTo($successors);
                        }
                        foreach ($legacy->users as $user) {
                            $user->givePermissionTo($successors);
                        }

                        $legacy->delete();
                    }
                }
            },
        );

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }

    /**
     * Restores the old permissions with the grants the catalog gave them and takes product deletion back from
     * frontend_user. The per-operation permissions stay.
     */
    public function down(): void
    {
        DB::transaction(
            function (): void {
                $adminRole = Role::findOrCreate('admin', 'backpack');
                foreach (array_keys(self::REPLACEMENTS['backpack']) as $name) {
                    $permission = Permission::findOrCreate($name, 'backpack');
                    $adminRole->givePermissionTo($permission);
                }

                foreach (self::FRONTEND_GRANTS as $name => $roleNames) {
                    $permission = Permission::findOrCreate($name, 'web');
                    foreach ($roleNames as $roleName) {
                        $role = Role::findOrCreate($roleName, 'web');
                        $role->givePermissionTo($permission);
                    }
                }

                $role = Role::findOrCreate('frontend_user', 'web');
                $role->revokePermissionTo('frontend.can_delete_product');
            },
        );

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }
};
