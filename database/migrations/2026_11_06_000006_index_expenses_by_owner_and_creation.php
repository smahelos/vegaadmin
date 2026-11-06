<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Serves the count of the monthly expense quota, like idx_invoices_user_created for invoices.
     */
    public function up(): void
    {
        Schema::table(
            'expenses',
            function (Blueprint $table): void {
                $table->index(['user_id', 'created_at'], 'idx_expenses_user_created');
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'expenses',
            function (Blueprint $table): void {
                $table->dropIndex('idx_expenses_user_created');
            },
        );
    }
};
