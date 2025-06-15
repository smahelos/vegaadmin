<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if we're running on SQLite (for testing) or MySQL (for production)
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support REGEXP, so we'll use a different approach
            // For testing, we'll just set all due_in values to 14 if they're not numeric
            DB::statement('UPDATE invoices SET due_in = 14 WHERE due_in NOT GLOB "[0-9]*" OR due_in IS NULL OR due_in = ""');
        } else {
            // MySQL version with REGEXP
            DB::statement('UPDATE invoices SET due_in = CAST(due_in AS UNSIGNED) WHERE due_in REGEXP \'^[0-9]+$\'');
            DB::statement('UPDATE invoices SET due_in = 14 WHERE due_in NOT REGEXP \'^[0-9]+$\' OR due_in IS NULL');
        }
        
        // Change column type to integer
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('due_in')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('due_in')->change();
        });
    }
};
