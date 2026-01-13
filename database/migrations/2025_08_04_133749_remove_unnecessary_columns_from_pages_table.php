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
        Schema::table('pages', function (Blueprint $table) {
            // Check which columns exist before dropping them
            $columnsToCheck = ['featured', 'show_in_menu', 'show_in_footer', 'page_data'];
            $existingColumns = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('pages', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            // Drop only existing columns
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Add back the columns if needed
            $table->boolean('featured')->default(false)->after('published_by');
            $table->boolean('show_in_menu')->default(false)->after('featured');
            $table->boolean('show_in_footer')->default(false)->after('show_in_menu');
            $table->json('page_data')->nullable()->after('images');
        });
    }
};
