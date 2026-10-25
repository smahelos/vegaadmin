<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('ico')->nullable();
            $table->string('dic')->nullable();
            $table->string('name');
            $table->string('shortcut')->nullable();
            $table->string('street');
            $table->string('city');
            $table->string('zip', 10);
            $table->string('country');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_clients_user_created');
            $table->index(['user_id', 'is_default'], 'idx_clients_user_default');
            $table->unique(['user_id', 'ico'], 'clients_user_ico_unique');
            $table->index(['user_id', 'name'], 'idx_clients_user_name_search');
            $table->index(['user_id', 'email'], 'idx_clients_user_email_search');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('ico')->nullable();
            $table->string('dic')->nullable();
            $table->string('name');
            $table->string('shortcut')->nullable();
            $table->string('street');
            $table->string('city');
            $table->string('zip', 10);
            $table->string('country');
            $table->string('phone')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->string('supplier_logo')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            $table->string('bank_name')->nullable();
            $table->boolean('has_payment_info')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_suppliers_user_created');
            $table->index(['user_id', 'is_default'], 'idx_suppliers_user_default');
            $table->unique(['user_id', 'ico'], 'suppliers_user_ico_unique');
            $table->index(['user_id', 'name'], 'idx_suppliers_user_name_search');
            $table->index(['user_id', 'email'], 'idx_suppliers_user_email_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('clients');
    }
};
