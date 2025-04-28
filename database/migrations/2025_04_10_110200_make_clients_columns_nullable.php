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
        Schema::table('clients', function (Blueprint $table) {
            // Úprava sloupců na nullable
            $table->string('ico')->nullable()->change();
            $table->string('dic')->nullable()->change();
            $table->string('shortcut')->nullable()->change();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Vrácení sloupců na not nullable (pokud je to výchozí stav)
            $table->string('ico')->nullable(false)->change();
            $table->string('dic')->nullable(false)->change();
            $table->string('shortcut')->nullable(false)->change();
        });
    }
};