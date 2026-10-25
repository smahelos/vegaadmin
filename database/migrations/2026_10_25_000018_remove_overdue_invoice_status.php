<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Overdue is derived from an unpaid status and a past due date, so it is no longer stored:
     * overdue invoices go back to pending and the overdue status row is removed.
     */
    public function up(): void
    {
        $overdueId = DB::table('statuses')->where('slug', 'overdue')->value('id');
        if ($overdueId === null) {
            return;
        }
        $pendingId = DB::table('statuses')->where('slug', 'pending')->value('id');

        DB::table('invoices')->where('payment_status_id', $overdueId)->update(['payment_status_id' => $pendingId]);
        DB::table('statuses')->where('id', $overdueId)->delete();
    }

    /**
     * Restores the status row; which invoices were overdue is not restored, the due dates tell.
     */
    public function down(): void
    {
        $categoryId = DB::table('status_categories')->where('slug', 'invoice-statuses')->value('id');
        if ($categoryId === null) {
            return;
        }

        DB::table('statuses')->insertOrIgnore([
            'name' => 'Po splatnosti',
            'slug' => 'overdue',
            'category_id' => $categoryId,
            'color' => 'red',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
