<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reference data the application needs to run in every environment: statuses, VAT rates, payment
 * methods, banks, categories, archive policies and the entity limits of the frontend roles. Rows
 * are matched by their natural key, so running it again updates them instead of adding copies.
 */
final class ReferenceDataSeeder extends Seeder
{
    /** @var array<string, array{name: string, statuses: list<array{string, string, string}>}> */
    private const STATUSES = [
        'invoice-statuses' => [
            'name' => 'Invoice statuses',
            'statuses' => [
                [
                    'paid',
                    'Uhrazená',
                    'green',
                ],
                [
                    'pending',
                    'Čeká na zaplacení',
                    'yellow',
                ],
                [
                    'partially-paid',
                    'Částečně uhrazená',
                    'blue',
                ],
                [
                    'cancelled',
                    'Zrušená',
                    'gray',
                ],
            ],
        ],
        'expense-statuses' => [
            'name' => 'Expense statuses',
            'statuses' => [
                [
                    'cancelled-expense',
                    'Cancelled',
                    'gray',
                ],
                [
                    'draft',
                    'Draft',
                    'yellow',
                ],
                [
                    'in-review',
                    'In Review',
                    'blue',
                ],
                [
                    'approved',
                    'Approved',
                    'pink',
                ],
                [
                    'rejected',
                    'Rejected',
                    'red',
                ],
                [
                    'paid-expense',
                    'Paid',
                    'green',
                ],
            ],
        ],
    ];

    /** @var list<string> */
    private const VAT_RATES = [
        '0.00',
        '12.00',
        '21.00',
    ];

    /** @var array<string, string> */
    private const PAYMENT_METHODS = [
        'bankaccount' => 'Bankovním převodem',
        'cash' => 'Hotově',
        'credit_card' => 'Platební karta',
        'paypal' => 'PayPal',
        'other' => 'Jiná platební metoda',
    ];

    /** @var list<array{string, string, string}> */
    private const BANKS = [
        [
            '0100',
            'Komerční banka',
            'KOMBCZPPXXX',
        ],
        [
            '0300',
            'ČSOB',
            'CEKOCZPP',
        ],
        [
            '0600',
            'MONETA Money Bank',
            'AGBACZPP',
        ],
        [
            '0800',
            'Česká spořitelna',
            'GIBACZPX',
        ],
        [
            '2010',
            'Fio banka',
            'FIOBCZPP',
        ],
        [
            '2700',
            'UniCredit Bank',
            'BACXCZPP',
        ],
        [
            '3030',
            'Air Bank',
            'AIRACZPP',
        ],
        [
            '5500',
            'Raiffeisenbank',
            'RZBCCZPP',
        ],
        [
            '6210',
            'mBank',
            'BREXCZPP',
        ],
    ];

    /** @var array<string, array{string, string, string}> */
    private const EXPENSE_CATEGORIES = [
        'operating-expenses' => [
            'Operating expenses',
            "Rent and Leases\nUtilities (Electricity, Gas, Water)\nInternet and Telecommunications\nInsurance",
            'red',
        ],
        'office-supplies' => [
            'Office Supplies',
            "Office Equipment\nStationery\nPrinting and Copying\nSoftware and Licenses",
            'yellow',
        ],
        'marketing-and-advertising' => [
            'Marketing and Advertising',
            "Online Advertising\nPrint Advertising\nPR Activities\nMarketing Materials",
            'blue',
        ],
        'travel-expenses' => [
            'Travel Expenses',
            "Business Trips\nFuel\nAccommodation\nPer Diems",
            'purple',
        ],
        'personnel-expenses' => [
            'Personnel Expenses',
            "Training and Education\nTeam Building\nRecruitment\nEmployee Benefits",
            'green',
        ],
        'external-services' => [
            'External Services',
            "Accounting and Tax Consulting\nLegal Services\nIT Services\nConsulting Services",
            'pink',
        ],
        'investments' => [
            'Investments',
            "Hardware\nSoftware\nReal Estate\nEquipment",
            'gray',
        ],
        'taxes-and-fees' => [
            'Taxes and Fees',
            "VAT\nIncome Tax\nRoad Tax\nAdministrative Fees",
            'indigo',
        ],
    ];

    /** @var array<string, array{string, string}> */
    private const PRODUCT_CATEGORIES = [
        'products' => ['Produkty', 'obyčejné produkty'],
        'services' => ['Služby', 'různé služby'],
    ];

    /** @var array<string, int> */
    private const ARCHIVE_RETENTION_MONTHS = [
        'invoices' => 36,
        'invoice_products' => 36,
    ];

    /**
     * Entity limits by permission: entity type, period, metric and limit value. A count limit counts what was
     * created in the period; an active limit counts the records that are not archived (businesses, contacts).
     *
     * @var array<string, array{string, string, string, int}>
     */
    private const ENTITY_LIMITS = [
        'limit_daily_invoices_basic' => [
            'invoice',
            'daily',
            'count',
            3,
        ],
        'limit_weekly_invoices_basic' => [
            'invoice',
            'weekly',
            'count',
            15,
        ],
        'limit_monthly_invoices_basic' => [
            'invoice',
            'monthly',
            'count',
            10,
        ],
        'limit_active_businesses_basic' => [
            'business',
            'lifetime',
            'active',
            10,
        ],
        'limit_active_contacts_basic' => [
            'contact',
            'lifetime',
            'active',
            16,
        ],
        'limit_lifetime_products_basic' => [
            'product',
            'lifetime',
            'count',
            5,
        ],
    ];

    /** @var array<string, list<string>> */
    private const ROLE_LIMITS = [
        'frontend_user' => [
            'limit_daily_invoices_basic',
            'limit_weekly_invoices_basic',
            'limit_monthly_invoices_basic',
            'limit_active_businesses_basic',
            'limit_active_contacts_basic',
            'limit_lifetime_products_basic',
        ],
        'frontend_user_plus' => [
            'limit_weekly_invoices_basic',
            'limit_monthly_invoices_basic',
            'limit_active_businesses_basic',
            'limit_active_contacts_basic',
            'limit_lifetime_products_basic',
        ],
    ];

    private const CRON_COMMAND = 'invoices:check-payment-status';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedStatuses();
            $this->seedTaxes();
            $this->seedPaymentMethods();
            $this->seedBanks();
            $this->seedExpenseCategories();
            $this->seedProductCategories();
            $this->seedArtisanCommands();
            $this->seedArchivePolicies();
            $this->seedEntityLimits();
        });
    }

    private function seedStatuses(): void
    {
        foreach (self::STATUSES as $categorySlug => $category) {
            $this->upsert(
                'status_categories',
                ['slug' => $categorySlug],
                ['name' => $category['name']],
            );
            $categoryId = DB::table('status_categories')->where('slug', $categorySlug)->value('id');

            foreach ($category['statuses'] as [$slug, $name, $color]) {
                $this->upsert(
                    'statuses',
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'category_id' => $categoryId,
                        'color' => $color,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    private function seedTaxes(): void
    {
        foreach (self::VAT_RATES as $rate) {
            $this->upsert(
                'taxes',
                ['slug' => 'dph', 'rate' => $rate],
                ['name' => 'DPH ' . (int) $rate . '%'],
            );
        }
    }

    private function seedPaymentMethods(): void
    {
        foreach (self::PAYMENT_METHODS as $slug => $name) {
            $this->upsert(
                'payment_methods',
                ['slug' => $slug],
                [
                    'name' => $name,
                    'country' => 'CZ',
                    'currency' => 'CZK',
                ],
            );
        }
    }

    private function seedBanks(): void
    {
        foreach (self::BANKS as [$code, $name, $swift]) {
            $this->upsert(
                'banks',
                ['code' => $code],
                [
                    'name' => $name,
                    'swift' => $swift,
                    'country' => 'CZ',
                    'active' => true,
                ],
            );
        }
    }

    private function seedExpenseCategories(): void
    {
        foreach (self::EXPENSE_CATEGORIES as $slug => [
            $name,
            $description,
            $color,
        ]) {
            $this->upsert(
                'expense_categories',
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'color' => $color,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedProductCategories(): void
    {
        foreach (self::PRODUCT_CATEGORIES as $slug => [$name, $description]) {
            $this->upsert(
                'product_categories',
                ['slug' => $slug],
                ['name' => $name, 'description' => $description],
            );
        }
    }

    /**
     * The categories and the command the predefined cron tasks run; artisan:sync-commands adds
     * every other command as uncategorized.
     */
    private function seedArtisanCommands(): void
    {
        $this->upsert(
            'artisan_command_categories',
            ['slug' => 'uncategorized'],
            [
                'name' => __('artisan_commands.categories.uncategorized.name'),
                'description' => __('artisan_commands.categories.uncategorized.description'),
                'is_active' => true,
            ],
        );
        $this->upsert(
            'artisan_command_categories',
            ['slug' => 'cron'],
            ['name' => 'Cron available commands', 'is_active' => true],
        );

        $this->upsert(
            'artisan_commands',
            ['command' => self::CRON_COMMAND],
            [
                'name' => self::CRON_COMMAND,
                'description' => 'Check invoice payment status and send reminders based on due dates',
                'category_id' => DB::table('artisan_command_categories')->where('slug', 'cron')->value('id'),
                'is_active' => true,
            ],
        );
    }

    private function seedArchivePolicies(): void
    {
        foreach (self::ARCHIVE_RETENTION_MONTHS as $table => $months) {
            $this->upsert(
                'archive_policies',
                ['table_name' => $table],
                [
                    'retention_months' => $months,
                    'date_column' => 'created_at',
                ],
            );
        }
    }

    private function seedEntityLimits(): void
    {
        foreach (self::ENTITY_LIMITS as $permission => [
            $entityType,
            $period,
            $metric,
            $limit,
        ]) {
            Permission::findOrCreate($permission, 'web');
            $this->upsert(
                'entity_limits',
                [
                    'permission_name' => $permission,
                    'entity_type' => $entityType,
                    'period_type' => $period,
                    'metric_type' => $metric,
                ],
                ['limit_value' => $limit, 'is_active' => 1],
            );
        }

        foreach (self::ROLE_LIMITS as $role => $permissions) {
            Role::findByName($role, 'web')->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $values
     */
    private function upsert(
        string $table,
        array $key,
        array $values,
    ): void {
        $now = now();
        $exists = DB::table($table)->where($key)->exists();

        if ($exists) {
            DB::table($table)->where($key)->update($values + ['updated_at' => $now]);

            return;
        }

        DB::table($table)->insert($key + $values + ['created_at' => $now, 'updated_at' => $now]);
    }
}
