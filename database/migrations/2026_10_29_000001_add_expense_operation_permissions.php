<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionCatalogSync::class)->sync();
        $legacy = Permission::where('name', 'can_create_edit_expense')->where('guard_name', 'backpack')->first();
        if ($legacy === null) {
            return;
        }
        foreach ($legacy->roles as $role) {
            $role->givePermissionTo([
                'can_view_expense',
                'can_create_expense',
                'can_update_expense',
                'can_delete_expense',
            ]);
        }
        foreach ($legacy->users as $user) {
            $user->givePermissionTo([
                'can_view_expense',
                'can_create_expense',
                'can_update_expense',
                'can_delete_expense',
            ]);
        }
    }

    public function down(): void {}
};
