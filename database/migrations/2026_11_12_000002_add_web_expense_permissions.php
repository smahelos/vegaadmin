<?php

declare(strict_types=1);

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create expense.create and expense.update in the web guard and grant them to every frontend role:
     * an admin writing an expense for an owner needs the owner's permission too.
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
