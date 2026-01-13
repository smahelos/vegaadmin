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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->string('gateway'); // gopay, stripe, etc.
            $table->string('gateway_payment_id')->nullable(); // External payment ID
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'partially_refunded'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('CZK');
            $table->string('payment_method')->nullable(); // card, bank_transfer, etc.
            $table->json('gateway_data')->nullable(); // Raw response from gateway
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['gateway', 'gateway_payment_id']);
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'paid_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
