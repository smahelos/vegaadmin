<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->nullable(); 
            $table->timestamps();

            $table->unique(['invoice_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_product');
    }
};
