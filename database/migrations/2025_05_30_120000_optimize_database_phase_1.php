<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Phase 1 Database Optimization
     * 
     * Critical optimizations:
     * 1. Fix issue_date data type (VARCHAR to DATE)
     * 2. Add missing foreign key constraints
     * 3. Optimize data types for better performance
     * 4. Add unique constraints for data integrity
     */
    public function up(): void
    {
        // Step 1: Fix issue_date data type in invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Add new date column temporarily
            $table->date('issue_date_new')->nullable()->after('issue_date');
        });
        
        // Convert existing datetime/varchar dates to proper DATE format
        // Handle different possible formats and set NULL for invalid dates
        DB::statement("
            UPDATE invoices 
            SET issue_date_new = CASE 
                WHEN issue_date IS NULL OR issue_date = '' OR issue_date = '0000-00-00' OR issue_date = '0000-00-00 00:00:00' THEN NULL
                WHEN issue_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN DATE(issue_date)
                ELSE NULL
            END
        ");
        
        Schema::table('invoices', function (Blueprint $table) {
            // Drop old VARCHAR column and rename new one
            $table->dropColumn('issue_date');
        });
        
        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('issue_date_new', 'issue_date');
            $table->date('issue_date')->nullable(false)->change();
        });

        // Step 2: Add missing foreign key constraints to invoices table
        // Check and add foreign key constraints only if they don't exist
        $existingConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'invoices' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $constraintNames = array_column($existingConstraints, 'CONSTRAINT_NAME');
        
        Schema::table('invoices', function (Blueprint $table) use ($constraintNames) {
            // Add FK constraints that are missing
            if (!in_array('invoices_client_id_foreign', $constraintNames)) {
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
            if (!in_array('invoices_user_id_foreign', $constraintNames)) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
            if (!in_array('invoices_payment_method_id_foreign', $constraintNames)) {
                $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
            }
            
            // Note: payment_status_id FK will be added when payment_statuses table exists
            // $table->foreign('payment_status_id')->references('id')->on('payment_statuses')->onDelete('set null');
        });

        // Step 3: Optimize data types in invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Optimize VARCHAR sizes for invoice numbers
            $table->string('invoice_vs', 50)->change();
            $table->string('invoice_ks', 20)->nullable()->change();
            $table->string('invoice_ss', 20)->nullable()->change();
            
            // Optimize currency field
            $table->string('payment_currency', 3)->change();
        });

        // Step 4: Optimize data types in other tables
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->string('currency', 3)->change();
        });

        // Step 5: Add unique constraints for data integrity
        Schema::table('banks', function (Blueprint $table) {
            // Make bank code unique if not already
            $existing_unique = DB::select("SHOW INDEX FROM banks WHERE Key_name = 'banks_code_unique'");
            if (empty($existing_unique)) {
                $table->unique('code', 'banks_code_unique');
            }
        });

        // Step 6: Add unique constraints for ICO (only where not null)
        // First, handle duplicate ICO values by setting newer entries to NULL
        // Use a more compatible approach for MySQL/MariaDB
        
        // For suppliers table
        DB::statement("
            UPDATE suppliers 
            SET ico = NULL 
            WHERE id IN (
                SELECT * FROM (
                    SELECT s1.id
                    FROM suppliers s1
                    INNER JOIN suppliers s2 ON s1.ico = s2.ico AND s1.ico IS NOT NULL AND s1.ico != ''
                    WHERE s1.id > s2.id
                ) AS duplicate_ids
            )
        ");
        
        // For clients table
        DB::statement("
            UPDATE clients 
            SET ico = NULL 
            WHERE id IN (
                SELECT * FROM (
                    SELECT c1.id
                    FROM clients c1
                    INNER JOIN clients c2 ON c1.ico = c2.ico AND c1.ico IS NOT NULL AND c1.ico != ''
                    WHERE c1.id > c2.id
                ) AS duplicate_ids
            )
        ");
        
        // Note: For MySQL/MariaDB, we create regular unique indexes on nullable columns
        // MySQL allows multiple NULL values in unique indexes, so this works as expected
        Schema::table('clients', function (Blueprint $table) {
            $existing_unique = DB::select("SHOW INDEX FROM clients WHERE Key_name = 'clients_ico_unique'");
            if (empty($existing_unique)) {
                $table->unique('ico', 'clients_ico_unique');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $existing_unique = DB::select("SHOW INDEX FROM suppliers WHERE Key_name = 'suppliers_ico_unique'");
            if (empty($existing_unique)) {
                $table->unique('ico', 'suppliers_ico_unique');
            }
        });

        // Step 7: Email constraints
        // Note: We do NOT add unique constraints on email for suppliers/clients
        // because the same supplier/client can exist for multiple users
        // Only users table should have unique email constraint (which already exists)

        // Step 8: Convert boolean-like tinyint fields to proper boolean
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->change();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->change();
            $table->boolean('has_payment_info')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse Step 8: Convert boolean back to tinyint
        Schema::table('suppliers', function (Blueprint $table) {
            $table->tinyInteger('is_default')->default(0)->change();
            $table->tinyInteger('has_payment_info')->default(0)->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->tinyInteger('is_default')->default(0)->change();
        });

        // Reverse Step 6: Drop ICO unique indexes
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_ico_unique');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropUnique('clients_ico_unique');
        });

        // Reverse Step 5: Drop bank code unique constraint
        Schema::table('banks', function (Blueprint $table) {
            $table->dropUnique('banks_code_unique');
        });

        // Reverse Step 4: Revert data types
        Schema::table('invoice_products', function (Blueprint $table) {
            $table->string('currency', 255)->change();
        });

        // Reverse Step 3: Revert invoices data types
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_vs', 255)->change();
            $table->string('invoice_ks', 255)->change();
            $table->string('invoice_ss', 255)->change();
            $table->string('payment_currency', 255)->change();
        });

        // Reverse Step 2: Drop foreign key constraints (only if they exist)
        $existingConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'invoices' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $constraintNames = array_column($existingConstraints, 'CONSTRAINT_NAME');
        
        Schema::table('invoices', function (Blueprint $table) use ($constraintNames) {
            if (in_array('invoices_payment_method_id_foreign', $constraintNames)) {
                $table->dropForeign(['payment_method_id']);
            }
            if (in_array('invoices_user_id_foreign', $constraintNames)) {
                $table->dropForeign(['user_id']);
            }
            if (in_array('invoices_client_id_foreign', $constraintNames)) {
                $table->dropForeign(['client_id']);
            }
        });

        // Reverse Step 1: Convert issue_date back to VARCHAR
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('issue_date_old', 255)->after('issue_date');
        });
        
        DB::statement("UPDATE invoices SET issue_date_old = DATE_FORMAT(issue_date, '%Y-%m-%d')");
        
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('issue_date');
            $table->renameColumn('issue_date_old', 'issue_date');
        });
    }
};
