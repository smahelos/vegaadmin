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
        Schema::table('artisan_commands', function (Blueprint $table) {
            $table->string('signature')->nullable()->after('command');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artisan_commands', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
