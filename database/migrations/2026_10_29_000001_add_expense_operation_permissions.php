<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $catalogSync = app(PermissionCatalogSync::class);
        $catalogSync->sync();
        $legacyQuery = Permission::where('name', 'can_create_edit_expense');
        $legacyQuery->where('guard_name', 'backpack');
        $legacy = $legacyQuery->first();
        if ($legacy === null) {
            return;
        }
        foreach ($legacy->roles as $role) {
            $role->givePermissionTo(
                [
                    'can_view_expense',
                    'can_create_expense',
                    'can_update_expense',
                    'can_delete_expense',
                ],
            );
        }
        foreach ($legacy->users as $user) {
            $user->givePermissionTo(
                [
                    'can_view_expense',
                    'can_create_expense',
                    'can_update_expense',
                    'can_delete_expense',
                ],
            );
        }
    }

    public function down(): void {}
};
