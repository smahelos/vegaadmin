<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The payment status of an invoice is an enum stored in its own column (D-050), not a row of the admin-managed
 * statuses table. Partially paid invoices become pending: partial payments come with payment records in step 18.
 */
return new class extends Migration
{
    /** @var list<array{string, string, string}> slug, name, colour of the rows that down() restores */
    private const STATUSES = [
        [
            'paid',
            'Uhrazená',
            'green',
        ],
        [
            'pending',
            'Čeká na zaplacení',
            'yellow',
        ],
        [
            'partially-paid',
            'Částečně uhrazená',
            'blue',
        ],
        [
            'cancelled',
            'Zrušená',
            'gray',
        ],
    ];

    public function up(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->string('payment_status', 20)->nullable()->after('due_in');
            },
        );

        $invoices = DB::table('invoices');
        $invoices->join(
            'statuses',
            'statuses.id',
            '=',
            'invoices.payment_status_id',
        );
        $invoices->update(
            [
                'invoices.payment_status' => DB::raw(
                    "CASE WHEN statuses.slug IN ('paid', 'cancelled') THEN statuses.slug ELSE 'pending' END",
                ),
            ],
        );
        $invoices = DB::table('invoices');
        $invoices->whereNull('payment_status');
        $invoices->update(['payment_status' => 'pending']);

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->string('payment_status', 20)->nullable(false)->change();
                $table->dropIndex('idx_invoices_user_payment_status');
                $table->dropConstrainedForeignId('payment_status_id');
                $table->index(['user_id', 'payment_status'], 'idx_invoices_user_payment_status');
            },
        );

        $categories = DB::table('status_categories');
        $categories->where('slug', 'invoice-statuses');
        $categoryId = $categories->value('id');
        if ($categoryId === null) {
            return;
        }
        $statuses = DB::table('statuses');
        $statuses->where('category_id', $categoryId);
        $statuses->delete();
        $categories = DB::table('status_categories');
        $categories->where('id', $categoryId);
        $categories->delete();
    }

    /** Partially paid invoices are not restored: they stay pending. */
    public function down(): void
    {
        $now = now();
        $categories = DB::table('status_categories');
        $categoryId = $categories->insertGetId(
            [
                'name' => 'Invoice statuses',
                'slug' => 'invoice-statuses',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
        foreach (self::STATUSES as [$slug, $name, $color]) {
            $statuses = DB::table('statuses');
            $statuses->insert(
                [
                    'name' => $name,
                    'slug' => $slug,
                    'category_id' => $categoryId,
                    'color' => $color,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->foreignId('payment_status_id')->nullable()->after('due_in')->constrained('statuses')->restrictOnDelete();
            },
        );
        $invoices = DB::table('invoices');
        $invoices->join(
            'statuses',
            'statuses.slug',
            '=',
            'invoices.payment_status',
        );
        $invoices->where('statuses.category_id', $categoryId);
        $invoices->update(
            ['invoices.payment_status_id' => DB::raw('statuses.id')],
        );

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->dropIndex('idx_invoices_user_payment_status');
                $table->dropColumn('payment_status');
                $table->index(['user_id', 'payment_status_id'], 'idx_invoices_user_payment_status');
            },
        );
    }
};
