<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            // Remove the type column
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table) {
            // Add the type column back if migration is rolled back
            $table->string('type')->nullable()->after('id');
        });
    }
};
