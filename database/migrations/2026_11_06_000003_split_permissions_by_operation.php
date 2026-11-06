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
     * Each permission that covered several operations, with the per-operation permissions that replace it. Roles
     * and users holding the old one get all of them. can_create_edit_command guarded the removed Artisan
     * command screens and has no successor; can_create_edit_invoice only showed the admin menu entry.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const REPLACEMENTS = [
        'backpack' => [
            'can_create_edit_bank' => [
                'can_view_bank',
                'can_create_bank',
                'can_update_bank',
                'can_delete_bank',
            ],
            'can_create_edit_command' => [],
            'can_create_edit_cron_task' => [
                'can_view_cron_task',
                'can_create_cron_task',
                'can_update_cron_task',
                'can_delete_cron_task',
                'can_run_cron_task',
            ],
            'can_create_edit_expense' => [
                'can_view_expense',
                'can_create_expense',
                'can_update_expense',
                'can_delete_expense',
            ],
            'can_create_edit_invoice' => ['can_view_invoice'],
            'can_create_edit_page' => [
                'can_view_page',
                'can_create_page',
                'can_update_page',
                'can_delete_page',
            ],
            'can_create_edit_payment_method' => [
                'can_view_payment_method',
                'can_create_payment_method',
                'can_update_payment_method',
                'can_delete_payment_method',
            ],
            'can_create_edit_product' => [
                'can_view_product',
                'can_create_product',
                'can_update_product',
                'can_delete_product',
            ],
            'can_create_edit_status' => [
                'can_view_status',
                'can_create_status',
                'can_update_status',
                'can_delete_status',
            ],
            'can_create_edit_tax' => [
                'can_view_tax',
                'can_create_tax',
                'can_update_tax',
                'can_delete_tax',
            ],
        ],
        'web' => [
            'frontend.can_create_edit_invoice' => [
                'frontend.can_create_invoice',
                'frontend.can_update_invoice',
            ],
        ],
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

                        if ($new !== []) {
                            foreach ($legacy->roles as $role) {
                                $role->givePermissionTo($new);
                            }
                            foreach ($legacy->users as $user) {
                                $user->givePermissionTo($new);
                            }
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
     * Restores the old permissions with the grants the catalog gave them: every backpack one to admin, the
     * frontend invoice permission to both frontend roles. The per-operation permissions stay.
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

                $permission = Permission::findOrCreate('frontend.can_create_edit_invoice', 'web');
                foreach (['frontend_user', 'frontend_user_plus'] as $roleName) {
                    $role = Role::findOrCreate($roleName, 'web');
                    $role->givePermissionTo($permission);
                }
            },
        );

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }
};
