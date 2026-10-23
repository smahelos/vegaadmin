<?php

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the permissions and roles of PermissionCatalog and grant the admin and system role
     * defaults. Additive: permissions granted through PermissionManager stay.
     */
    public function up(): void
    {
        app(PermissionCatalogSync::class)->sync();
    }

    /**
     * Permissions may already be granted to users and roles, so they are kept.
     */
    public function down(): void {}
};
