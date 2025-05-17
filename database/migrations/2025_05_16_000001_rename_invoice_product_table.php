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
        // Check if the old table exists before attempting to rename
        if (Schema::hasTable('invoice_product') && !Schema::hasTable('invoice_products')) {
            Schema::rename('invoice_product', 'invoice_products');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the new table exists before attempting to rename back
        if (Schema::hasTable('invoice_products') && !Schema::hasTable('invoice_product')) {
            Schema::rename('invoice_products', 'invoice_product');
        }
    }
};
