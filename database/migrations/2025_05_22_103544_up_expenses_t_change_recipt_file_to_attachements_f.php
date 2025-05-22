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
        Schema::table('expenses', function (Blueprint $table) {
            $table->text('attachments')->default(null)->after('receipt_file');
        });
        
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('receipt_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('receipt_file')->nullable();
        });
    }
};
