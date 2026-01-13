<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0.00)->after('amount');
            }
            // Extend enum: in SQLite enum stored as TEXT, so we cannot alter easily; we document logical new statuses.
            if (!Schema::hasColumn('payments', 'original_status_enum_note')) {
                $table->string('original_status_enum_note')->nullable()->comment('Documentation field: enum extended with partially_refunded');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'refunded_amount')) {
                $table->dropColumn('refunded_amount');
            }
            if (Schema::hasColumn('payments', 'original_status_enum_note')) {
                $table->dropColumn('original_status_enum_note');
            }
        });
    }
};
