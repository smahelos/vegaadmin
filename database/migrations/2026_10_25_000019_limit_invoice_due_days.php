<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * InvoiceDueDate accepts 0 to 365 due days (InvoiceDueDate::MAX_DAYS); the database refuses any other value,
     * so every stored invoice has a due date that can be computed and printed.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_due_in_range CHECK (due_in BETWEEN 0 AND 365)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoices DROP CHECK invoices_due_in_range');
    }
};
