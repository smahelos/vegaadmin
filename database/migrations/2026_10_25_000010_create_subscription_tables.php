<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal(
                'price',
                10,
                2,
            );
            $table->string('currency', 3);
            $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');
            $table->integer('billing_interval')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('trial_days')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'billing_period']);
        });

        Schema::create('subscription_plan_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index('slug');
        });

        Schema::create('subscription_plan_subscription_plan_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')
                ->constrained(indexName: 'sp_spf_plan_foreign')
                ->cascadeOnDelete();
            $table->foreignId('subscription_plan_feature_id')
                ->constrained(indexName: 'sp_spf_feature_foreign')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['subscription_plan_id', 'subscription_plan_feature_id'], 'sp_spf_unique');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'pending',
                'active',
                'cancelled',
                'expired',
                'past_due',
            ])->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->decimal(
                'amount',
                10,
                2,
            );
            $table->string('currency', 3);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'next_billing_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->string('gateway');
            $table->string('gateway_payment_id')->nullable();
            $table->enum(
                'status',
                [
                    'pending',
                    'processing',
                    'completed',
                    'failed',
                    'cancelled',
                    'refunded',
                    'partially_refunded',
                ],
            )->nullable()->default('pending');
            $table->decimal(
                'amount',
                10,
                2,
            );
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->string('currency', 3);
            $table->string('payment_method')->nullable();
            $table->json('gateway_data')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['gateway', 'gateway_payment_id']);
            $table->index(['subscription_id', 'status']);
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plan_subscription_plan_feature');
        Schema::dropIfExists('subscription_plan_features');
        Schema::dropIfExists('subscription_plans');
    }
};
