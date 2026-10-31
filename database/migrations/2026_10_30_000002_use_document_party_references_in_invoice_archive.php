<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices_archive', function (Blueprint $table): void {
            // Archive references survive erasure of source records, like original_id and user_id.
            $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('contact_id')->nullable()->after('business_id');
            $table->dropColumn('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices_archive', function (Blueprint $table): void {
            $table->unsignedBigInteger('client_id')->nullable()->after('user_id');
            $table->dropColumn(['business_id', 'contact_id']);
        });
    }
};
