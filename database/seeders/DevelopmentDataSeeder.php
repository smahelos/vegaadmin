<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Development accounts and their data: an admin, a backend user and two users of each frontend
 * role. Every frontend user gets parties, products, invoices over the last fourteen months and
 * expenses, so that every list and dashboard chart has something to show. Running it again
 * deletes the accounts' data and creates it anew; the data is the same on every run, dated
 * relative to today. Every account signs in with the password "backpack". Local and testing
 * environments only.
 *
 *     php artisan db:seed --class=DevelopmentDataSeeder
 */
final class DevelopmentDataSeeder extends Seeder
{
    public const PASSWORD = 'backpack';

    /**
     * Accounts by e-mail; frontend accounts get data.
     *
     * @var array<string, array{name: string, guard: string, role: string}>
     */
    public const ACCOUNTS = [
        'admin@billex.test' => [
            'name' => 'Admin',
            'guard' => PermissionCatalog::BACKPACK,
            'role' => PermissionCatalog::ADMIN_ROLE,
        ],
        'backend@billex.test' => [
            'name' => 'Backend',
            'guard' => PermissionCatalog::BACKPACK,
            'role' => 'backend_user',
        ],
        'user1@billex.test' => [
            'name' => 'User 1',
            'guard' => PermissionCatalog::WEB,
            'role' => 'frontend_user',
        ],
        'user2@billex.test' => [
            'name' => 'User 2',
            'guard' => PermissionCatalog::WEB,
            'role' => 'frontend_user',
        ],
        'plus1@billex.test' => [
            'name' => 'Plus 1',
            'guard' => PermissionCatalog::WEB,
            'role' => 'frontend_user_plus',
        ],
        'plus2@billex.test' => [
            'name' => 'Plus 2',
            'guard' => PermissionCatalog::WEB,
            'role' => 'frontend_user_plus',
        ],
    ];

    private const MONTHS = 14;

    private const DUE_IN_DAYS = 14;

    private Randomizer $random;

    public function __construct(
        private readonly InvoiceCrudApplicationServiceInterface $invoices,
    ) {}

    public function run(): void
    {
        // The accounts are overwritten and their data deleted, so never on a shared environment
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DevelopmentDataSeeder runs only in the local and testing environments');
        }

        DB::transaction(function (): void {
            $index = 0;
            foreach (self::ACCOUNTS as $email => $account) {
                $user = $this->account($email, $account);
                if ($account['guard'] === PermissionCatalog::WEB) {
                    $this->createData($user, $index);
                }
                $index++;
            }
        });
    }

    /**
     * @param  array{name: string, guard: string, role: string}  $account
     */
    private function account(string $email, array $account): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $account['name'],
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
        ])->save();
        $user->syncRoles([Role::findByName($account['role'], $account['guard'])]);

        return $user;
    }

    /**
     * Each account has its own random sequence, so adding an account leaves the others' data as it was.
     */
    private function createData(User $user, int $index): void
    {
        $this->random = new Randomizer(new Mt19937(2026 + $index));
        $this->deleteData($user);

        $supplier = $this->createSupplier($user);
        $clients = $this->createClients($user);
        $products = $this->createProducts($user);

        $this->createInvoices(
            $user,
            $supplier,
            $clients,
            $products,
        );
        $this->createExpenses($user);

        // Every invoice above was counted as created today; the account starts with no usage
        DB::table('entity_limit_usage')->where('user_id', $user->id)->delete();
    }

    private function deleteData(User $user): void
    {
        $invoiceIds = DB::table('invoices')->where('user_id', $user->id)->pluck('id');
        DB::table('invoice_products')->whereIn('invoice_id', $invoiceIds)->delete();

        foreach ([
            'invoices',
            'expenses',
            'products',
            'clients',
            'suppliers',
            'entity_limit_usage',
        ] as $table) {
            DB::table($table)->where('user_id', $user->id)->delete();
        }
    }

    private function createSupplier(User $user): Supplier
    {
        return Supplier::query()->create([
            'user_id' => $user->id,
            'name' => $user->name . ' Studio s.r.o.',
            'email' => 'fakturace' . $user->id . '@dev-studio.test',
            'phone' => '+420 601 234 567',
            'street' => 'Vinohradská 12',
            'city' => 'Praha',
            'zip' => '12000',
            'country' => 'CZ',
            'ico' => $this->companyNumber($user, 0),
            'dic' => 'CZ' . $this->companyNumber($user, 0),
            'account_number' => '2001234567',
            'bank_code' => '2010',
            'bank_name' => 'Fio banka',
            'iban' => 'CZ6520100000002001234567',
            'swift' => 'FIOBCZPP',
            'has_payment_info' => true,
            'is_default' => true,
        ]);
    }

    /**
     * @return list<Client>
     */
    private function createClients(User $user): array
    {
        $clients = [
            [
                'Alfa Stavby a.s.',
                'Brno',
                '60200',
                'Masarykova 4',
            ],
            [
                'Beta Logistika s.r.o.',
                'Ostrava',
                '70200',
                'Nádražní 88',
            ],
            [
                'Gama Software s.r.o.',
                'Praha',
                '17000',
                'Dukelských hrdinů 5',
            ],
            [
                'Delta Pekárny s.r.o.',
                'Plzeň',
                '30100',
                'Americká 21',
            ],
            [
                'Epsilon Design',
                'Olomouc',
                '77900',
                'Horní náměstí 3',
            ],
            [
                'Zeta Consulting GmbH',
                'Wien',
                '1010',
                'Graben 17',
            ],
        ];

        $created = [];
        foreach ($clients as $index => [
            $name,
            $city,
            $zip,
            $street,
        ]) {
            $number = $this->companyNumber($user, $index + 1);
            $created[] = Client::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'email' => 'ucetni' . $user->id . '-' . ($index + 1) . '@dev-client.test',
                'phone' => '+420 70' . $index . ' 111 222',
                'street' => $street,
                'city' => $city,
                'zip' => $zip,
                'country' => $city === 'Wien' ? 'AT' : 'CZ',
                'ico' => $number,
                'dic' => ($city === 'Wien' ? 'ATU' : 'CZ') . $number,
                'is_default' => $index === 0,
            ]);
        }

        return $created;
    }

    /**
     * @return list<Product>
     */
    private function createProducts(User $user): array
    {
        $products = [
            [
                'Konzultace',
                Unit::HOURS,
                '1200.00',
                '21.00',
            ],
            [
                'Vývoj webové aplikace',
                Unit::HOURS,
                '1500.00',
                '21.00',
            ],
            [
                'Správa serveru',
                Unit::PIECES,
                '2500.00',
                '21.00',
            ],
            [
                'Školení',
                Unit::DAYS,
                '9000.00',
                '21.00',
            ],
            [
                'Webhosting',
                Unit::PIECES,
                '450.00',
                '21.00',
            ],
            [
                'Grafický návrh',
                Unit::HOURS,
                '900.00',
                '21.00',
            ],
            [
                'Odborná publikace',
                Unit::PIECES,
                '650.00',
                '12.00',
            ],
        ];

        $created = [];
        foreach ($products as $index => [
            $name,
            $unit,
            $price,
            $rate,
        ]) {
            $created[] = Product::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'slug' => 'dev-' . $user->id . '-' . ($index + 1),
                'price' => $price,
                'currency' => Currency::CZK->value,
                'unit' => $unit->value,
                'tax_id' => $this->taxId($rate),
                'is_default' => $index === 0,
            ]);
        }

        return $created;
    }

    /**
     * Two to five invoices a month; older ones are mostly paid, the last few weeks still open.
     *
     * @param  list<Client>  $clients
     * @param  list<Product>  $products
     */
    private function createInvoices(
        User $user,
        Supplier $supplier,
        array $clients,
        array $products,
    ): void {
        $today = new DateTimeImmutable('today');
        $paymentMethods = $this->referenceIds('payment_methods');
        $sequences = [];

        for ($monthsBack = self::MONTHS - 1; $monthsBack >= 0; $monthsBack--) {
            $month = $today->modify('first day of this month')->modify('-' . $monthsBack . ' months');
            $issueDates = [];
            for ($i = $this->random->getInt(2, 5); $i > 0; $i--) {
                $issueDates[] = $month->modify('+' . $this->random->getInt(0, 27) . ' days');
            }
            sort($issueDates);

            foreach ($issueDates as $issueDate) {
                if ($issueDate > $today) {
                    continue;
                }

                $year = (int) $issueDate->format('Y');
                $sequences[$year] = ($sequences[$year] ?? 0) + 1;
                $client = $clients[$this->random->getInt(0, count($clients) - 1)];
                $currency = $client->country === 'AT' ? Currency::EUR : Currency::CZK;

                $this->invoices->create(
                    new UserId($user->id),
                    [
                        'invoice_vs' => InvoiceNumber::fromYearAndSequence($year, $sequences[$year])->getNumber(),
                        'issue_date' => $issueDate->format('Y-m-d'),
                        'tax_point_date' => $issueDate->format('Y-m-d'),
                        'due_in' => self::DUE_IN_DAYS,
                        'client_id' => $client->id,
                        'supplier_id' => $supplier->id,
                        'payment_method_id' => $paymentMethods[$this->random->getInt(0, count($paymentMethods) - 1)],
                        'payment_status_id' => $this->statusId('invoice-statuses', $this->invoiceStatus($issueDate, $today)->value),
                        'payment_currency' => $currency->value,
                    ],
                    $this->invoiceLines($products, $currency),
                );
            }
        }
    }

    /**
     * @param  list<Product>  $products
     * @return list<InvoiceProductWriteData>
     */
    private function invoiceLines(array $products, Currency $currency): array
    {
        $lines = [];
        $picked = $this->random->pickArrayKeys($products, $this->random->getInt(1, 3));
        foreach ($picked as $index) {
            $product = $products[$index];
            // Foreign clients are billed in EUR at a fixed demo rate; the products are priced in CZK
            $price = $currency === Currency::CZK
                ? (string) $product->price
                : number_format(
                    (float) $product->price / 25,
                    2,
                    '.',
                    '',
                );

            $lines[] = new InvoiceProductWriteData(
                name: $product->name,
                quantity: (string) $this->random->getInt(1, 12),
                unit: Unit::from($product->unit),
                price: Money::fromString($price, $currency->value),
                taxRate: (string) DB::table('taxes')->where('id', $product->tax_id)->value('rate'),
                productId: ProductId::fromInt($product->id),
            );
        }

        return $lines;
    }

    /**
     * Unpaid invoices past their due date show as overdue.
     */
    private function invoiceStatus(DateTimeImmutable $issueDate, DateTimeImmutable $today): InvoiceStatus
    {
        $dueDate = $issueDate->modify('+' . self::DUE_IN_DAYS . ' days');
        $roll = $this->random->getInt(1, 100);

        if ($dueDate >= $today) {
            return $roll <= 30 ? InvoiceStatus::PAID : InvoiceStatus::PENDING;
        }

        return match (true) {
            $roll <= 75 => InvoiceStatus::PAID,
            $roll <= 85 => InvoiceStatus::PARTIALLY_PAID,
            $roll <= 95 => InvoiceStatus::PENDING,
            default => InvoiceStatus::CANCELLED,
        };
    }

    private function createExpenses(User $user): void
    {
        $today = new DateTimeImmutable('today');
        $categories = $this->referenceIds('expense_categories');
        $paidStatus = $this->statusId('expense-statuses', 'paid-expense');
        $descriptions = [
            'Nájem kanceláře',
            'Software a licence',
            'Telefon a internet',
            'Cestovné',
            'Kancelářské potřeby',
            'Reklama',
        ];

        for ($monthsBack = self::MONTHS - 1; $monthsBack >= 0; $monthsBack--) {
            $month = $today->modify('first day of this month')->modify('-' . $monthsBack . ' months');
            for ($i = $this->random->getInt(2, 4); $i > 0; $i--) {
                $date = $month->modify('+' . $this->random->getInt(0, 27) . ' days');
                if ($date > $today) {
                    continue;
                }

                $amount = $this->random->getInt(800, 18000);
                Expense::query()->create([
                    'user_id' => $user->id,
                    'category_id' => $categories[$this->random->getInt(0, count($categories) - 1)],
                    'expense_date' => $date->format('Y-m-d'),
                    'amount' => $amount . '.00',
                    'currency' => Currency::CZK->value,
                    'tax_amount' => number_format(
                        $amount * 21 / 121,
                        2,
                        '.',
                        '',
                    ),
                    'tax_included' => true,
                    'description' => $descriptions[$this->random->getInt(0, count($descriptions) - 1)],
                    'status_id' => $paidStatus,
                    'attachments' => [],
                ]);
            }
        }
    }

    /**
     * IČO is unique across all users, so each account gets its own range.
     */
    private function companyNumber(User $user, int $offset): string
    {
        return (string) (10000000 + $user->id * 100 + $offset);
    }

    /**
     * @return non-empty-list<int>
     */
    private function referenceIds(string $table): array
    {
        $ids = DB::table($table)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        if ($ids === []) {
            throw new RuntimeException('Table ' . $table . ' is empty; run ReferenceDataSeeder first');
        }

        return array_values($ids);
    }

    private function taxId(string $rate): int
    {
        $id = DB::table('taxes')->where('rate', $rate)->value('id');
        if ($id === null) {
            throw new RuntimeException('Tax rate ' . $rate . ' % is missing; run ReferenceDataSeeder first');
        }

        return (int) $id;
    }

    private function statusId(string $category, string $slug): int
    {
        $id = DB::table('statuses')
            ->join(
                'status_categories',
                'statuses.category_id',
                '=',
                'status_categories.id',
            )
            ->where('status_categories.slug', $category)
            ->where('statuses.slug', $slug)
            ->value('statuses.id');
        if ($id === null) {
            throw new RuntimeException('Status ' . $category . '/' . $slug . ' is missing; run ReferenceDataSeeder first');
        }

        return (int) $id;
    }
}
