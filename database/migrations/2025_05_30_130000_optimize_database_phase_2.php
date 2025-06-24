<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class OptimizeDatabasePhase2 extends Migration
{
    /**
     * Run the migrations.
     * Phase 2: Composite indexes and performance optimization based on common query patterns
     *
     * @return void
     */
    public function up()
    {
        // Only show progress in production environment, not during tests
        if (!app()->environment('testing')) {
            echo "Phase 2: Adding composite indexes and performance optimizations...\n";
        }

        // 1. INVOICES TABLE - Most critical composite indexes
        Schema::table('invoices', function (Blueprint $table) {
            // Primary user filtering with chronological sorting
            $table->index(['user_id', 'created_at'], 'idx_invoices_user_created');
            $table->index(['user_id', 'issue_date'], 'idx_invoices_user_issue_date');
            
            // Dashboard queries (user + client relationships)
            $table->index(['user_id', 'client_id'], 'idx_invoices_user_client');
            
            // Payment status checking (user + payment calculations)
            $table->index(['user_id', 'issue_date', 'due_in'], 'idx_invoices_payment_calc');
            
            // Payment status filtering with user context
            $table->index(['user_id', 'payment_status_id'], 'idx_invoices_user_payment_status');
            
            // Invoice number lookups within user context
            $table->index(['user_id', 'invoice_vs'], 'idx_invoices_user_invoice_vs');
        });

        // 2. CLIENTS TABLE - User-scoped operations
        Schema::table('clients', function (Blueprint $table) {
            // Primary user filtering with chronological sorting
            $table->index(['user_id', 'created_at'], 'idx_clients_user_created');
            
            // Default client selection per user
            $table->index(['user_id', 'is_default'], 'idx_clients_user_default');
            
            // ICO searches within user context (business logic allows duplicates across users)
            $table->index(['user_id', 'ico'], 'idx_clients_user_ico');
        });

        // 3. SUPPLIERS TABLE - User-scoped operations  
        Schema::table('suppliers', function (Blueprint $table) {
            // Primary user filtering with chronological sorting
            $table->index(['user_id', 'created_at'], 'idx_suppliers_user_created');
            
            // Default supplier selection per user
            $table->index(['user_id', 'is_default'], 'idx_suppliers_user_default');
            
            // ICO searches within user context (business logic allows duplicates across users)
            $table->index(['user_id', 'ico'], 'idx_suppliers_user_ico');
        });

        // 4. INVOICE_PRODUCTS TABLE - Performance for product calculations
        Schema::table('invoice_products', function (Blueprint $table) {
            // Invoice detail loading (most common query)
            $table->index(['invoice_id', 'created_at'], 'idx_invoice_products_invoice_created');
            
            // Product-based analytics if needed
            $table->index(['product_id', 'created_at'], 'idx_invoice_products_product_created');
        });

        // 5. PRODUCTS TABLE - User-scoped product management
        Schema::table('products', function (Blueprint $table) {
            // User product listing with sorting
            $table->index(['user_id', 'created_at'], 'idx_products_user_created');
            $table->index(['user_id', 'name'], 'idx_products_user_name');
        });

        // 6. TEXT SEARCH PERFORMANCE INDEXES
        // For name-based searches (most common in frontend)
        Schema::table('clients', function (Blueprint $table) {
            // Text search within user context
            $table->index(['user_id', 'name'], 'idx_clients_user_name_search');
            $table->index(['user_id', 'email'], 'idx_clients_user_email_search');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            // Text search within user context  
            $table->index(['user_id', 'name'], 'idx_suppliers_user_name_search');
            $table->index(['user_id', 'email'], 'idx_suppliers_user_email_search');
        });

        // 7. AUDIT/LOGGING PERFORMANCE (if audit tables exist)
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->index(['subject_type', 'subject_id', 'created_at'], 'idx_activity_subject_created');
                $table->index(['causer_type', 'causer_id', 'created_at'], 'idx_activity_causer_created');
            });
        }

        // 8. SESSION AND CACHE PERFORMANCE
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['user_id', 'last_activity'], 'idx_sessions_user_activity');
            });
        }

        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                $table->index(['expiration'], 'idx_cache_expiration');
            });
        }

        // Note: users table indexes not needed - it already has proper primary key and unique email index

        if (!app()->environment('testing')) {
            echo "Phase 2 optimization completed successfully!\n";
            echo "Added " . $this->getIndexCount() . " composite indexes for optimal query performance.\n";
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!app()->environment('testing')) {
            echo "Rolling back Phase 2 optimizations...\n";
        }

        // Remove composite indexes in reverse order
        if (Schema::hasTable('cache')) {
            Schema::table('cache', function (Blueprint $table) {
                $table->dropIndex('idx_cache_expiration');
            });
        }

        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('idx_sessions_user_activity');
            });
        }

        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex('idx_activity_subject_created');
                $table->dropIndex('idx_activity_causer_created');
            });
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('idx_suppliers_user_name_search');
            $table->dropIndex('idx_suppliers_user_email_search');
            $table->dropIndex('idx_suppliers_user_created');
            $table->dropIndex('idx_suppliers_user_default');
            $table->dropIndex('idx_suppliers_user_ico');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_user_name_search');
            $table->dropIndex('idx_clients_user_email_search');
            $table->dropIndex('idx_clients_user_created');
            $table->dropIndex('idx_clients_user_default');
            $table->dropIndex('idx_clients_user_ico');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_user_created');
            $table->dropIndex('idx_products_user_name');
        });

        Schema::table('invoice_products', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_products_invoice_created');
            $table->dropIndex('idx_invoice_products_product_created');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_user_created');
            $table->dropIndex('idx_invoices_user_issue_date');
            $table->dropIndex('idx_invoices_user_client');
            $table->dropIndex('idx_invoices_payment_calc');
            $table->dropIndex('idx_invoices_user_payment_status');
            $table->dropIndex('idx_invoices_user_invoice_vs');
        });

        if (!app()->environment('testing')) {
            echo "Phase 2 rollback completed.\n";
        }
    }

    /**
     * Get total count of indexes being added
     *
     * @return int
     */
    private function getIndexCount(): int
    {
        return 20; // Total composite indexes being added (reduced from 22 after removing users indexes)
    }
}
