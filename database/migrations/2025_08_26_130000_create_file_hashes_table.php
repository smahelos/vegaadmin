<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('file_hashes', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50);
            $table->string('path', 512); // stored relative path
            $table->string('hash', 64)->index(); // sha256
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('ref_count')->default(1);
            $table->timestamps();
            $table->unique(['disk','path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_hashes');
    }
};
