<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create statistics.view in the backpack guard for the admin dashboard and grant it to the admin role.
     * Additive: permissions granted through PermissionManager stay.
     */
    public function up(): void
    {
        $catalogSync = app(PermissionCatalogSync::class);
        $catalogSync->sync();
    }

    /**
     * Permissions may already be granted to users and roles, so they are kept.
     */
    public function down(): void {}
};
