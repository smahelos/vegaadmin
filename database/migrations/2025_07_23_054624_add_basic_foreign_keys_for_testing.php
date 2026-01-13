<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add basic foreign key constraints needed for testing
     */
    public function up(): void
    {
        // Only run in testing environment with SQLite
        if (!app()->environment('testing') || DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        // Add foreign key constraints for proper cascade delete behavior in testing
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
        });

        // Change payment_amount to decimal for proper currency handling in testing
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('payment_amount', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run in testing environment with SQLite
        if (!app()->environment('testing') || DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['payment_method_id']);
        });

        // Revert payment_amount back to integer
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('payment_amount')->change();
        });
    }
};
