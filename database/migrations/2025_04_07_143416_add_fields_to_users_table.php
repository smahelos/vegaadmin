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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ico')->nullable()->after('remember_token');
            $table->string('dic')->nullable()->after('ico');
            $table->string('street')->nullable()->after('dic');
            $table->string('city')->nullable()->after('street');
            $table->string('zip')->nullable()->after('city');
            $table->string('country')->nullable()->after('zip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ico', 'dic', 'street', 'city', 'zip', 'country']);
        });
    }
};
