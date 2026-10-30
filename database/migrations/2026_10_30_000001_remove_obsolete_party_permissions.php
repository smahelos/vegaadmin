<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** @var list<string> */
    private const BACKPACK_PERMISSIONS = [
        'backpack.api.clients',
        'backpack.api.suppliers',
        'can_create_edit_client',
        'can_create_edit_supplier',
        'can_create_client',
        'can_create_supplier',
        'can_update_client',
        'can_update_supplier',
        'can_delete_client',
        'can_delete_supplier',
        'can_view_client',
        'can_view_supplier',
        'can_create_client_unlimited',
        'can_create_supplier_unlimited',
    ];

    /** @var list<string> */
    private const WEB_PERMISSIONS = [
        'frontend.api.clients',
        'frontend.api.suppliers',
        'frontend.can_create_edit_client',
        'frontend.can_create_edit_supplier',
        'frontend.can_delete_client',
        'frontend.can_delete_supplier',
        'frontend.can_view_client',
        'frontend.can_view_supplier',
    ];

    /** @var list<string> */
    private const BASIC_DEFAULTS = [
        'frontend.api.clients',
        'frontend.api.suppliers',
        'frontend.can_create_edit_client',
        'frontend.can_create_edit_supplier',
        'frontend.can_view_client',
        'frontend.can_view_supplier',
    ];

    /** @var list<string> */
    private const PLUS_DEFAULTS = [
        'frontend.api.clients',
        'frontend.api.suppliers',
        'frontend.can_create_edit_client',
        'frontend.can_create_edit_supplier',
        'frontend.can_delete_client',
        'frontend.can_delete_supplier',
        'frontend.can_view_client',
        'frontend.can_view_supplier',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            Permission::where('guard_name', 'backpack')->whereIn('name', self::BACKPACK_PERMISSIONS)->delete();
            Permission::where('guard_name', 'web')->whereIn('name', self::WEB_PERMISSIONS)->delete();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::BACKPACK_PERMISSIONS as $name) {
                Permission::findOrCreate($name, 'backpack');
            }
            foreach (self::WEB_PERMISSIONS as $name) {
                Permission::findOrCreate($name, 'web');
            }
            Role::findOrCreate('admin', 'backpack')->givePermissionTo(self::BACKPACK_PERMISSIONS);
            Role::findOrCreate('frontend_user', 'web')->givePermissionTo(self::BASIC_DEFAULTS);
            Role::findOrCreate('frontend_user_plus', 'web')->givePermissionTo(self::PLUS_DEFAULTS);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
