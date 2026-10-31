<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A document keeps its own copy of both parties and of the payment account, so later changes to the
     * address book never change it; business, contact and account only record where the copy came from.
     */
    public function up(): void
    {
        foreach (['invoices', 'expenses'] as $documents) {
            if (DB::table($documents)->exists()) {
                throw new RuntimeException('Stored ' . $documents . ' have no business to belong to; rebuild the database with migrate:fresh --seed');
            }
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropIndex('idx_invoices_user_client');
            $table->dropColumn(['client_id', 'supplier_id']);
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $this->references($table);
            $this->partyColumns(
                $table,
                'supplier',
                true,
            );
            $this->partyColumns(
                $table,
                'customer',
                true,
            );
            $this->accountColumns($table);

            $table->foreign(['bank_account_id', 'business_id'], 'invoices_bank_account_foreign')
                ->references(['id', 'business_id'])
                ->on('bank_accounts')
                ->restrictOnDelete();
            $table->index(['user_id', 'contact_id']);
            $table->index(['user_id', 'customer_name']);
        });

        // On an expense the contact is the supplier and the business the customer, who does not need a full address
        Schema::table('expenses', function (Blueprint $table): void {
            $this->references($table);
            $this->partyColumns(
                $table,
                'supplier',
                true,
            );
            $this->partyColumns(
                $table,
                'customer',
                false,
            );
            $this->accountColumns($table);

            $table->foreign(['bank_account_id', 'contact_id'], 'expenses_bank_account_foreign')
                ->references(['id', 'contact_id'])
                ->on('bank_accounts')
                ->restrictOnDelete();
            $table->index(['user_id', 'contact_id']);
        });

        // MySQL does not check a composite key with a NULL column, so an account without a contact would pass
        DB::statement('ALTER TABLE expenses ADD CONSTRAINT expenses_bank_account_contact CHECK (bank_account_id IS NULL OR contact_id IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE expenses DROP CHECK expenses_bank_account_contact');

        // (user_id, contact_id) serves the user key, whose own index MySQL dropped; the key is recreated so that
        // MySQL creates and later drops that index on its own again
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });

        foreach (['invoices', 'expenses'] as $documents) {
            Schema::table($documents, function (Blueprint $table) use ($documents): void {
                // MySQL keeps the index it created for a foreign key, shortened to the columns that remain
                foreach ([
                    '_bank_account_foreign',
                    '_contact_foreign',
                    '_business_foreign',
                ] as $key) {
                    $table->dropForeign($documents . $key);
                    $table->dropIndex($documents . $key);
                }
                $table->dropIndex($documents . '_user_id_contact_id_index');
                if ($documents === 'invoices') {
                    $table->dropIndex('invoices_user_id_customer_name_index');
                }
                $table->dropColumn([
                    'business_id',
                    'contact_id',
                    'bank_account_id',
                    ...$this->partyColumnNames('supplier'),
                    ...$this->partyColumnNames('customer'),
                    ...$this->accountColumnNames(),
                ]);
            });
        }

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('client_id')->nullable()->after('id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
            $table->index(['user_id', 'client_id'], 'idx_invoices_user_client');
        });
    }

    /**
     * The composite keys allow only a business and a contact of the document's user.
     */
    private function references(Blueprint $table): void
    {
        $documents = $table->getTable();

        $table->unsignedBigInteger('business_id')->after('user_id');
        $table->unsignedBigInteger('contact_id')->nullable()->after('business_id');
        $table->unsignedBigInteger('bank_account_id')->nullable()->after('contact_id');

        $table->foreign(['business_id', 'user_id'], $documents . '_business_foreign')
            ->references(['id', 'user_id'])
            ->on('businesses')
            ->restrictOnDelete();
        $table->foreign(['contact_id', 'user_id'], $documents . '_contact_foreign')
            ->references(['id', 'user_id'])
            ->on('contacts')
            ->restrictOnDelete();
    }

    /**
     * Name, street, city, postal code and country are the party data every document needs.
     */
    private function partyColumns(
        Blueprint $table,
        string $role,
        bool $addressRequired,
    ): void {
        $table->string($role . '_name');
        $table->string($role . '_company_number')->nullable();
        $table->char($role . '_registration_country', 2)->nullable();
        $table->string($role . '_vat_number')->nullable();
        $table->string($role . '_street')->nullable(! $addressRequired);
        $table->string($role . '_city')->nullable(! $addressRequired);
        $table->string($role . '_postal_code', 20)->nullable(! $addressRequired);
        $table->char($role . '_country', 2)->nullable(! $addressRequired);
        $table->string($role . '_email')->nullable();
        $table->string($role . '_phone')->nullable();
    }

    private function accountColumns(Blueprint $table): void
    {
        $table->string('account_iban', 34)->nullable();
        $table->string('account_number', 17)->nullable();
        $table->string('account_bank_code', 10)->nullable();
        $table->char('account_country', 2)->nullable();
        $table->string('account_swift', 11)->nullable();
        $table->string('account_bank_name')->nullable();
    }

    /**
     * @return list<string>
     */
    private function partyColumnNames(string $role): array
    {
        return array_map(
            fn (string $column): string => $role . '_' . $column,
            [
                'name',
                'company_number',
                'registration_country',
                'vat_number',
                'street',
                'city',
                'postal_code',
                'country',
                'email',
                'phone',
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function accountColumnNames(): array
    {
        return [
            'account_iban',
            'account_number',
            'account_bank_code',
            'account_country',
            'account_swift',
            'account_bank_name',
        ];
    }
};
