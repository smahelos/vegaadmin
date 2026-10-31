<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table): void {
            $this->partyColumns($table);
            $table->string('logo')->nullable();
            $this->partyKeys($table);
        });

        Schema::create('contacts', function (Blueprint $table): void {
            $this->partyColumns($table);
            $table->string('contact_person')->nullable();
            $this->partyKeys($table);
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->char('country', 2)->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('label')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('number', 17)->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('swift', 11)->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            // Targets of the composite keys that keep a default or a document account with its owner
            $table->unique(['id', 'business_id']);
            $table->unique(['id', 'contact_id']);
        });

        // Every address and account belongs to exactly one owner
        DB::statement('ALTER TABLE addresses ADD CONSTRAINT addresses_one_owner CHECK ((business_id IS NULL) <> (contact_id IS NULL))');
        DB::statement('ALTER TABLE bank_accounts ADD CONSTRAINT bank_accounts_one_owner CHECK ((business_id IS NULL) <> (contact_id IS NULL))');

        // The default account is a single pointer that can only point to an account of the same owner
        Schema::table('businesses', function (Blueprint $table): void {
            $table->foreign(['default_bank_account_id', 'id'], 'businesses_default_bank_account_foreign')
                ->references(['id', 'business_id'])
                ->on('bank_accounts')
                ->restrictOnDelete();
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->foreign(['default_bank_account_id', 'id'], 'contacts_default_bank_account_foreign')
                ->references(['id', 'contact_id'])
                ->on('bank_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropForeign('contacts_default_bank_account_foreign');
        });

        Schema::table('businesses', function (Blueprint $table): void {
            $table->dropForeign('businesses_default_bank_account_foreign');
        });

        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('businesses');
    }

    private function partyColumns(Blueprint $table): void
    {
        $table->id();
        $table->foreignId('user_id')->constrained()->restrictOnDelete();
        $table->string('name');
        $table->string('short_name')->nullable();
        $table->char('registration_country', 2)->nullable();
        $table->string('company_number')->nullable();
        // A Czech IČO identifies the party within the account, archived records included; foreign numbers
        // only warn, so they stay out of the unique key (MySQL ignores NULL there)
        $table->string('czech_company_number')
            ->nullable()
            ->virtualAs("IF(registration_country = 'CZ', company_number, NULL)");
        $table->string('vat_number')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->text('note')->nullable();
        $table->unsignedBigInteger('default_bank_account_id')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->timestamps();
    }

    private function partyKeys(Blueprint $table): void
    {
        // Target of the composite keys that keep a document or a default selection within the owner's account
        $table->unique(['id', 'user_id']);
        $table->unique(['user_id', 'czech_company_number']);
        $table->index(['user_id', 'company_number']);
        $table->index([
            'user_id',
            'archived_at',
            'name',
        ]);
        $table->index(['user_id', 'email']);
    }
};
