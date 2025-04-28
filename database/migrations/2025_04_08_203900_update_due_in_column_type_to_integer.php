<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nejprve změníme hodnoty na číselné, kde je to potřeba
        DB::statement('UPDATE invoices SET due_in = CAST(due_in AS UNSIGNED) WHERE due_in REGEXP \'^[0-9]+$\'');
        
        // Pro hodnoty, které nelze převést, nastavíme výchozí hodnotu 14
        DB::statement('UPDATE invoices SET due_in = 14 WHERE due_in NOT REGEXP \'^[0-9]+$\' OR due_in IS NULL');
        
        // Nyní změníme typ sloupce na integer
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('due_in')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('due_in')->change();
        });
    }
};
