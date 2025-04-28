<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('statuses', function (Blueprint $table) {
            // Přidání sloupce color, pokud ještě neexistuje
            if (!Schema::hasColumn('statuses', 'color')) {
                $table->string('color')->nullable()->after('slug')->comment('CSS třída určující barvu stavu');
            }
            
            // Přidání sloupce is_active, pokud ještě neexistuje
            if (!Schema::hasColumn('statuses', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('color')->comment('Zda je stav aktivní a lze ho vybrat');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('statuses', function (Blueprint $table) {
            // Odstranění sloupců při rollbacku
            $table->dropColumn(['color', 'is_active']);
        });
    }
};