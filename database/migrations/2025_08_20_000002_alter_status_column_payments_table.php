<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.default');
        $driver = config('database.connections.' . $connection . '.driver');

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled','refunded','partially_refunded') DEFAULT 'pending'");
            return;
        }

        // SQLite: enum was already stored as TEXT; allow new logical value without schema change.
        if ($driver === 'sqlite') { return; }

        // Fallback: attempt simple alteration to string if supported
        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('status', 32)->default('pending')->change();
            });
        } catch (Throwable $e) {
            // Log but do not fail migration in unsupported drivers
            info('Could not alter payments.status column: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        $connection = config('database.default');
    $driver = config('database.connections.' . $connection . '.driver');
        if ($driver === 'mysql') {
            // Revert enum (without partially_refunded)
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending'");
        }
        // For sqlite we leave the widened string (non destructive down)
    }
};
