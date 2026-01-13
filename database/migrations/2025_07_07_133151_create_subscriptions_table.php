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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'cancelled', 'expired', 'past_due'])->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('CZK');
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_billing_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
