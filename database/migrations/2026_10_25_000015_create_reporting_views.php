<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW user_activity_summary AS
            SELECT
                u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email,
                COUNT(DISTINCT i.id) AS total_invoices,
                COUNT(DISTINCT c.id) AS total_clients,
                COUNT(DISTINCT s.id) AS total_suppliers,
                COUNT(DISTINCT p.id) AS total_products,
                MAX(i.created_at) AS last_invoice_date,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS invoices_last_30_days,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS invoices_last_7_days
            FROM users u
            LEFT JOIN invoices i ON u.id = i.user_id
            LEFT JOIN clients c ON u.id = c.user_id
            LEFT JOIN suppliers s ON u.id = s.user_id
            LEFT JOIN products p ON u.id = p.user_id
            GROUP BY u.id, u.name, u.email
            SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW invoice_statistics AS
            SELECT
                DATE_FORMAT(issue_date, '%Y-%m') AS month_year,
                user_id,
                COUNT(*) AS invoice_count,
                SUM(payment_amount) AS total_revenue,
                AVG(payment_amount) AS avg_invoice_amount,
                MIN(payment_amount) AS min_invoice_amount,
                MAX(payment_amount) AS max_invoice_amount,
                COUNT(CASE WHEN payment_status_id = 1 THEN 1 END) AS paid_invoices,
                COUNT(CASE WHEN payment_status_id = 2 THEN 1 END) AS unpaid_invoices,
                COUNT(CASE WHEN payment_status_id = 3 THEN 1 END) AS overdue_invoices
            FROM invoices
            WHERE issue_date >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
            GROUP BY DATE_FORMAT(issue_date, '%Y-%m'), user_id
            ORDER BY month_year DESC, user_id
            SQL);

        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW database_size_monitor AS
            SELECT
                TABLE_NAME AS table_name,
                TABLE_ROWS AS row_count,
                ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
                ROUND(DATA_LENGTH / 1024 / 1024, 2) AS data_size_mb,
                ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS index_size_mb,
                ENGINE AS storage_engine
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS database_size_monitor');
        DB::statement('DROP VIEW IF EXISTS invoice_statistics');
        DB::statement('DROP VIEW IF EXISTS user_activity_summary');
    }
};
