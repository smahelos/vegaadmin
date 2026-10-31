<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Archived businesses and contacts are counted too: the summary reports what an account holds.
     */
    public function up(): void
    {
        $this->createView('businesses', 'contacts');
    }

    public function down(): void
    {
        $this->createView('clients', 'suppliers');
    }

    private function createView(string $firstParties, string $secondParties): void
    {
        $firstTotal = 'total_' . $firstParties;
        $secondTotal = 'total_' . $secondParties;

        DB::statement(<<<SQL
            CREATE OR REPLACE VIEW user_activity_summary AS
            SELECT
                u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email,
                COUNT(DISTINCT i.id) AS total_invoices,
                COUNT(DISTINCT c.id) AS {$firstTotal},
                COUNT(DISTINCT s.id) AS {$secondTotal},
                COUNT(DISTINCT p.id) AS total_products,
                MAX(i.created_at) AS last_invoice_date,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS invoices_last_30_days,
                SUM(CASE WHEN i.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS invoices_last_7_days
            FROM users u
            LEFT JOIN invoices i ON u.id = i.user_id
            LEFT JOIN {$firstParties} c ON u.id = c.user_id
            LEFT JOIN {$secondParties} s ON u.id = s.user_id
            LEFT JOIN products p ON u.id = p.user_id
            GROUP BY u.id, u.name, u.email
            SQL);
    }
};
