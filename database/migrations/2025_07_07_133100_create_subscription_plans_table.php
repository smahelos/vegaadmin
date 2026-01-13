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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('CZK');
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->integer('billing_interval')->default(1); // Every X months/years
            $table->json('features')->nullable(); // JSON array of features
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(0); // Free trial days
            $table->timestamps();
            
            $table->index(['is_active', 'billing_period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
