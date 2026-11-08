<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The monthly quota window starts no earlier than the assignment of the current plan role (D-047);
     * without it the window is the calendar month.
     */
    public function up(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->timestamp('plan_role_assigned_at')->nullable();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'users',
            function (Blueprint $table): void {
                $table->dropColumn('plan_role_assigned_at');
            },
        );
    }
};
