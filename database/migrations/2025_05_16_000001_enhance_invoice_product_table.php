<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First check if product_id column is nullable, if not make it nullable
        if (Schema::hasColumn('invoice_product', 'product_id')) {
            Schema::table('invoice_product', function (Blueprint $table) {
                // Make sure product_id can be NULL for custom products
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });
        }

        Schema::table('invoice_product', function (Blueprint $table) {
            // Add columns for pivot table enhancement
            $table->string('currency')->default('CZK')->after('price');
            $table->string('unit')->nullable()->after('currency');
            $table->string('category')->nullable()->after('unit');
            $table->string('description')->nullable()->after('category');
            
            // Flag to distinguish product types
            $table->boolean('is_custom_product')->default(false)->after('description');
            
            // Add columns for calculations
            $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
            $table->decimal('total_price', 10, 2)->default(0)->after('tax_amount');
            
            // Add foreign key with nullOnDelete option
            // Need to check if foreign key doesn't already exist to avoid duplicate errors
            if (!$this->hasForeignKey('invoice_product', 'product_id', 'products')) {
                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_product', function (Blueprint $table) {
            // Try to remove foreign key first if it exists
            if ($this->hasForeignKey('invoice_product', 'product_id', 'products')) {
                $table->dropForeign(['product_id']);
            }
            
            $table->dropColumn([
                'currency',
                'unit',
                'category',
                'description',
                'is_custom_product',
                'tax_amount',
                'total_price'
            ]);
        });
    }
    
    /**
     * Check if the table has a specific foreign key
     *
     * @param string $table
     * @param string $column
     * @param string $referencedTable
     * @return bool
     */
    private function hasForeignKey(string $table, string $column, string $referencedTable): bool
    {
        // Get database connection and platform
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'sqlite') {
            // For SQLite, check pragma foreign_key_list
            try {
                $foreignKeys = DB::select("PRAGMA foreign_key_list($table)");
                foreach ($foreignKeys as $fk) {
                    if ($fk->from == $column && $fk->table == $referencedTable) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                // If table doesn't exist or other error, assume no foreign key
                return false;
            }
        } else {
            // MySQL/MariaDB specific query to check for foreign keys
            $databaseName = $connection->getDatabaseName();
            $foreignKeys = DB::select("
                SELECT * 
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
                AND REFERENCED_TABLE_NAME = ?
            ", [$databaseName, $table, $column, $referencedTable]);
            
            return count($foreignKeys) > 0;
        }
    }
};
