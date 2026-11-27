<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const REMOVED = [
        'invoice.create',
        'invoice.update',
        'invoice.delete',
    ];

    /**
     * The admin shows invoices read-only (D-050), so its guard no longer holds the write permissions.
     * Deleting a permission removes its grants to roles and users.
     */
    public function up(): void
    {
        $query = Permission::query();
        $query->where('guard_name', 'backpack');
        $query->whereIn('name', self::REMOVED);
        $query->delete();

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }

    /**
     * Restores the permissions; the admin role gets them back from the catalog of the previous version.
     */
    public function down(): void
    {
        $roleQuery = Role::query();
        $roleQuery->where('guard_name', 'backpack');
        $roleQuery->where('name', 'admin');
        $admin = $roleQuery->first();
        foreach (self::REMOVED as $name) {
            $permission = Permission::findOrCreate($name, 'backpack');
            $admin?->givePermissionTo($permission);
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }
};
