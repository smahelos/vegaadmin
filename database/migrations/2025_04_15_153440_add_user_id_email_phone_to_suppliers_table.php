<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Přidání sloupce user_id jako cizí klíč
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            
            // Přidání sloupců email a phone
            $table->string('phone', 255)->nullable()->after('country');
            $table->string('email', 255)->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Odstranění cizího klíče
            $table->dropForeign(['user_id']);
            
            // Odstranění sloupců
            $table->dropColumn(['user_id', 'email', 'phone']);
        });
    }
};