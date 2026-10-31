<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create the business and contact permissions of PermissionCatalog and grant them to the admin and the
     * frontend roles. Additive: permissions granted through PermissionManager stay.
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
