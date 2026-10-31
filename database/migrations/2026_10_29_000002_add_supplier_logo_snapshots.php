<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', static function (Blueprint $table): void {
            $table->string('logo')->nullable();
        });
        Schema::table('invoices', static function (Blueprint $table): void {
            $table->renameColumn('invoice_logo', 'supplier_logo');
        });
        Schema::table('expenses', static function (Blueprint $table): void {
            $table->string('supplier_logo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', static function (Blueprint $table): void {
            $table->dropColumn('supplier_logo');
        });
        Schema::table('invoices', static function (Blueprint $table): void {
            $table->renameColumn('supplier_logo', 'invoice_logo');
        });
        Schema::table('contacts', static function (Blueprint $table): void {
            $table->dropColumn('logo');
        });
    }
};
