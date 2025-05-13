<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes the zip column type from integer to string in clients and suppliers tables
     */
    public function up(): void
    {
        // Change zip column type in clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->string('zip', 10)->change();
        });

        // Change zip column type in suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('zip', 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     * Changes the zip column type back from string to integer
     */
    public function down(): void
    {
        // Change zip column type back in clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->integer('zip')->change();
        });

        // Change zip column type back in suppliers table
        Schema::table('suppliers', function (Blueprint $table) {
            $table->integer('zip')->change();
        });
    }
};
