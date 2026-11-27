<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Grant invoice.delete to frontend_user: a user deletes their own drafts (D-050, point 2).
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
