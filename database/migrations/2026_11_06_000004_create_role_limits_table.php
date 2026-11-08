<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The limits of a plan role (D-047): one column per limited entity, NULL unlimited, 0 not allowed.
     */
    public function up(): void
    {
        Schema::create(
            'role_limits',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('role_id')->unique()->constrained('roles')->cascadeOnDelete();
                $table->unsignedInteger('invoices')->nullable();
                $table->unsignedInteger('expenses')->nullable();
                $table->unsignedInteger('businesses')->nullable();
                $table->unsignedInteger('contacts')->nullable();
                $table->unsignedInteger('products')->nullable();
                $table->timestamps();
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('role_limits');
    }
};
