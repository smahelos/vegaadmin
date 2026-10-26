<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_hashes', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 50);
            $table->string('path', 512);
            $table->string('hash', 64)->index();
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('ref_count')->default(1);
            $table->timestamps();

            $table->unique(['disk', 'path']);
            $table->index(['hash', 'disk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_hashes');
    }
};
