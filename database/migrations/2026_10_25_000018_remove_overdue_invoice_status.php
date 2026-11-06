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
        $statuses = DB::table('statuses');
        $statuses->where('slug', 'overdue');
        $overdueId = $statuses->value('id');
        if ($overdueId === null) {
            return;
        }
        $statuses = DB::table('statuses');
        $statuses->where('slug', 'pending');
        $pendingId = $statuses->value('id');
        if ($pendingId === null) {
            throw new RuntimeException(
                'The pending invoice status is missing; overdue invoices would be left without a status.',
            );
        }

        $invoices = DB::table('invoices');
        $invoices->where('payment_status_id', $overdueId);
        $invoices->update(['payment_status_id' => $pendingId]);
        $statuses = DB::table('statuses');
        $statuses->where('id', $overdueId);
        $statuses->delete();
    }

    /**
     * Restores the status row; which invoices were overdue is not restored, the due dates tell.
     */
    public function down(): void
    {
        $categories = DB::table('status_categories');
        $categories->where('slug', 'invoice-statuses');
        $categoryId = $categories->value('id');
        if ($categoryId === null) {
            return;
        }

        $statuses = DB::table('statuses');
        $statuses->insertOrIgnore(
            [
                'name' => 'Po splatnosti',
                'slug' => 'overdue',
                'category_id' => $categoryId,
                'color' => 'red',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
};
