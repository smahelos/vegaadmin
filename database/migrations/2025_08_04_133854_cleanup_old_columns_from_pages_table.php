<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Check which old columns exist before dropping them
            $columnsToCheck = ['name_old', 'slug_old', 'description_old', 'content_old'];
            $existingColumns = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('pages', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            // Drop only existing old columns
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
            // Add back old columns if rollback is needed
            $table->string('name_old')->after('category_id');
            $table->string('slug_old')->after('name_old');
            $table->text('description_old')->nullable()->after('slug_old');
            $table->longText('content_old')->nullable()->after('description_old');
        });
    }
};
