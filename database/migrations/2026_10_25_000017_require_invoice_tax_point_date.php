<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A tax document states its tax point date; an invoice stored without one took it on its issue date.
     */
    public function up(): void
    {
        $invoices = DB::table('invoices');
        $invoices->whereNull('tax_point_date');
        $invoices->update(
            ['tax_point_date' => DB::raw('issue_date')],
        );

        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->date('tax_point_date')->nullable(false)->change();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'invoices',
            function (Blueprint $table): void {
                $table->date('tax_point_date')->nullable()->change();
            },
        );
    }
};
