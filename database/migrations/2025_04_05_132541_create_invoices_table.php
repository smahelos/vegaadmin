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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('payment_method_id')->nullable()->index();
            $table->string('invoice_vs');
            $table->string('invoice_ks');
            $table->string('invoice_ss');
            $table->string('payback_days');
            $table->string('payment_status');
            $table->string('payment_draft_date');
            $table->integer('payment_amount');
            $table->string('payment_currency');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
