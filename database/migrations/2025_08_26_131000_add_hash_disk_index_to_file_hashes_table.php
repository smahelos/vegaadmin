<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('file_hashes', function (Blueprint $table) {
            $table->index(['hash','disk']);
        });
    }
    public function down(): void
    {
        Schema::table('file_hashes', function (Blueprint $table) {
            $table->dropIndex('file_hashes_hash_disk_index');
        });
    }
};
