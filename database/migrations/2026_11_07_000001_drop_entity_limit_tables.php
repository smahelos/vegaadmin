<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Limits are rows of role_limits, usage is counted from the entities' rows (D-047 point 11).
     */
    public function up(): void
    {
        DB::transaction(
            function (): void {
                $limitQuery = DB::table('entity_limits');
                $names = $limitQuery->pluck('permission_name');
                $permissions = Permission::where('guard_name', 'web');
                $permissions->whereIn('name', $names);
                $permissions->delete();
            },
        );
        Schema::drop('entity_limit_usage');
        Schema::drop('entity_limits');
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
    }

    /**
     * Restores the empty tables only: the limit rows and the limit_* permissions are not recreated.
     */
    public function down(): void
    {
        $createTables = require __DIR__ . '/2026_10_25_000003_create_entity_limit_tables.php';
        $createTables->up();
    }
};
