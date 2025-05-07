table.php

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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('description');
            $table->string('bank_code')->nullable()->after('account_number');
            $table->string('iban')->nullable()->after('bank_code');
            $table->string('swift')->nullable()->after('iban');
            $table->string('bank_name')->nullable()->after('swift');
            $table->boolean('has_payment_info')->default(false)->after('bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'account_number',
                'bank_code',
                'iban',
                'swift',
                'bank_name',
                'has_payment_info'
            ]);
        });
    }
};