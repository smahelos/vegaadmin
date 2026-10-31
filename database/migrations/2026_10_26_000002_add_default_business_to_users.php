<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The default business prefills new documents; the composite key allows only a business of the same user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('default_business_id')->nullable();

            $table->foreign(['default_business_id', 'id'], 'users_default_business_foreign')
                ->references(['id', 'user_id'])
                ->on('businesses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign('users_default_business_foreign');
            $table->dropColumn('default_business_id');
        });
    }
};
