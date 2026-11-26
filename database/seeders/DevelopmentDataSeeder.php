<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Infrastructure\Authorization\PermissionCatalog;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\User;
use Database\Factories\CzechCompanyNumbers;
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
 * role. Every frontend user gets businesses, contacts, products, invoices and expenses over the last
 * fourteen months, so that every list and dashboard chart has something to show. Running it again
 * deletes the accounts' data and creates it anew; the data is the same on every run, dated
 * relative to today. Every account signs in with the password "backpack". Local and testing
 * environments only.
 *
 *     php artisan db:seed --class=DevelopmentDataSeeder
 */
final class DevelopmentDataSeeder extends Seeder
{
    use CzechCompanyNumbers;

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

    public function run(): void
    {
        // The accounts are overwritten and their data deleted, so never on a shared environment
        $application = app();
        if (!$application->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'DevelopmentDataSeeder runs only in the local and testing environments',
            );
        }

        DB::transaction(
            function (): void {
                $index = 0;
                foreach (self::ACCOUNTS as $email => $account) {
                    $user = $this->account($email, $account);
                    if ($account['guard'] === PermissionCatalog::WEB) {
                        $this->createData($user, $index);
                    }
                    $index++;
                }
            },
        );
    }

    /**
     * @param  array{name: string, guard: string, role: string}  $account
     */
    private function account(string $email, array $account): User
    {
        $userQuery = User::query();
        $user = $userQuery->firstOrNew(['email' => $email]);
        $user->forceFill(
            [
                'name' => $account['name'],
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $user->save();
        $user->syncRoles(
            [Role::findByName($account['role'], $account['guard'])],
        );

        return $user;
    }

    /**
     * Each account has its own random sequence, so adding an account leaves the others' data as it was.
     */
    private function createData(User $user, int $index): void
    {
        $this->random = new Randomizer(
            new Mt19937(2026 + $index),
        );
        $this->deleteData($user);

        $business = $this->createBusinesses($user);
        $customers = $this->createContacts($user);
        $suppliers = $this->createExpenseSuppliers($user);
        $catalog = $this->createProducts($user);
        $this->createInvoices(
            $user,
            $business,
            $customers,
            $catalog,
        );
        $this->createExpenses(
            $user,
            $business,
            $suppliers,
        );
    }

    private function deleteData(User $user): void
    {
        $query = DB::table('invoices');
        $query->where('user_id', $user->id);
        $invoiceIds = $query->pluck('id');

        $query = DB::table('invoice_products');
        $query->whereIn('invoice_id', $invoiceIds);
        $query->delete();

        $query = DB::table('invoices');
        $query->where('user_id', $user->id);
        $query->delete();

        $query = DB::table('expenses');
        $query->where('user_id', $user->id);
        $query->delete();

        // Default selections point into the rows deleted below
        $user->forceFill(['default_business_id' => null]);
        $user->save();

        $query = DB::table('businesses');
        $query->where('user_id', $user->id);
        $businessIds = $query->pluck('id');

        $query = DB::table('contacts');
        $query->where('user_id', $user->id);
        $contactIds = $query->pluck('id');

        $query = DB::table('businesses');
        $query->whereIn('id', $businessIds);
        $query->update(['default_bank_account_id' => null]);

        $query = DB::table('contacts');
        $query->whereIn('id', $contactIds);
        $query->update(['default_bank_account_id' => null]);

        foreach (['addresses', 'bank_accounts'] as $table) {
            $query = DB::table($table);
            $query->whereIn('business_id', $businessIds);
            $query->delete();

            $query = DB::table($table);
            $query->whereIn('contact_id', $contactIds);
            $query->delete();
        }

        foreach (
            [
                'businesses',
                'contacts',
                'products',
            ] as $table
        ) {
            $query = DB::table($table);
            $query->where('user_id', $user->id);
            $query->delete();
        }
    }

    /**
     * Two active businesses and an archived one; the first is the default VAT payer, with a Czech account and a
     * second account in EUR, the second a non-payer.
     */
    private function createBusinesses(User $user): Business
    {
        $default = $this->createParty(
            Business::class,
            $user,
            [
                'name' => $user->name . ' Studio s.r.o.',
                'short_name' => 'Studio',
                'company_number' => $this->companyNumber($user, 0),
                'vat_number' => 'CZ' . $this->companyNumber($user, 0),
                'is_vat_payer' => true,
                'register_entry' => 'C 123456 vedená u Městského soudu v Praze',
                'email' => 'fakturace' . $user->id . '@dev-studio.test',
                'phone' => '+420 601 234 567',
            ],
            [
                'Vinohradská 12',
                'Praha',
                '120 00',
                'CZ',
            ],
        );
        $this->createAccount(
            $default,
            [
                'label' => 'Hlavní',
                'number' => '2001234561',
                'bank_code' => '2010',
                'iban' => 'CZ1520100000002001234561',
                'swift' => 'FIOBCZPP',
                'bank_name' => 'Fio banka',
            ],
            true,
        );
        $this->createAccount(
            $default,
            [
                'label' => 'EUR',
                'number' => '2101234569',
                'bank_code' => '2010',
                'iban' => 'CZ3720100000002101234569',
                'swift' => 'FIOBCZPP',
                'bank_name' => 'Fio banka',
            ],
            false,
        );
        $user->forceFill(['default_business_id' => $default->id]);
        $user->save();

        $second = $this->createParty(
            Business::class,
            $user,
            [
                'name' => $user->name . ' Školení s.r.o.',
                'company_number' => $this->companyNumber($user, 1),
                'register_entry' => 'C 234567 vedená u Krajského soudu v Brně',
                'email' => 'skoleni' . $user->id . '@dev-studio.test',
            ],
            [
                'Kounicova 7',
                'Brno',
                '602 00',
                'CZ',
            ],
        );
        $this->createAccount(
            $second,
            [
                'number' => '1234567899',
                'bank_code' => '0100',
                'iban' => 'CZ9501000000001234567899',
                'swift' => 'KOMBCZPP',
                'bank_name' => 'Komerční banka',
            ],
            true,
        );

        $this->createParty(
            Business::class,
            $user,
            [
                'name' => $user->name . ' Ateliér s.r.o.',
                'company_number' => $this->companyNumber($user, 2),
                'archived_at' => now(),
            ],
            [
                'Pernštýnské náměstí 1',
                'Pardubice',
                '530 02',
                'CZ',
            ],
        );

        return $default;
    }

    /**
     * Customers: one Austrian, one without a company number and one archived.
     *
     * @return non-empty-list<Contact> the active Czech customers, which invoices are issued to
     */
    private function createContacts(User $user): array
    {
        $customers = [];
        $contacts = [
            [
                'Alfa Stavby a.s.',
                'Masarykova 4',
                'Brno',
                '602 00',
            ],
            [
                'Beta Logistika s.r.o.',
                'Nádražní 88',
                'Ostrava',
                '702 00',
            ],
            [
                'Gama Software s.r.o.',
                'Dukelských hrdinů 5',
                'Praha',
                '170 00',
            ],
            [
                'Delta Pekárny s.r.o.',
                'Americká 21',
                'Plzeň',
                '301 00',
            ],
            [
                'Epsilon Design',
                'Horní náměstí 3',
                'Olomouc',
                '779 00',
            ],
        ];

        foreach (
            $contacts as $index => [
                $name,
                $street,
                $city,
                $postalCode,
            ]
        ) {
            $withoutNumber = $name === 'Epsilon Design';
            $archived = $name === 'Delta Pekárny s.r.o.';
            $contact = $this->createParty(
                Contact::class,
                $user,
                [
                    'name' => $name,
                    'registration_country' => $withoutNumber ? null : 'CZ',
                    'company_number' => $withoutNumber ? null : $this->companyNumber($user, 10 + $index),
                    'vat_number' => $withoutNumber ? null : 'CZ' . $this->companyNumber($user, 10 + $index),
                    'email' => 'ucetni' . $user->id . '-' . ($index + 1) . '@dev-client.test',
                    'phone' => '+420 70' . $index . ' 111 222',
                    'archived_at' => $archived ? now() : null,
                ],
                [
                    $street,
                    $city,
                    $postalCode,
                    'CZ',
                ],
            );
            if (!$archived) {
                $customers[] = $contact;
            }
        }

        $this->createParty(
            Contact::class,
            $user,
            [
                'name' => 'Zeta Consulting GmbH',
                'registration_country' => 'AT',
                'company_number' => 'FN ' . (100000 + $user->id) . 'a',
                'vat_number' => 'ATU' . (10000000 + $user->id),
                'email' => 'office' . $user->id . '@dev-zeta.test',
                'contact_person' => 'Mag. Anna Huber',
            ],
            [
                'Graben 17',
                'Wien',
                '1010',
                'AT',
            ],
        );

        // The Austrian customer is left out: its invoices would need the reverse charge, not Czech VAT
        return $customers;
    }

    /**
     * Two contacts the expenses are paid to, each with an account.
     *
     * @return list<Contact>
     */
    private function createExpenseSuppliers(User $user): array
    {
        $office = $this->createParty(
            Contact::class,
            $user,
            [
                'name' => 'Kancelářské centrum Vinohrady s.r.o.',
                'short_name' => 'Nájem',
                'company_number' => $this->companyNumber($user, 20),
                'vat_number' => 'CZ' . $this->companyNumber($user, 20),
                'email' => 'najem' . $user->id . '@dev-supplier.test',
            ],
            [
                'Korunní 40',
                'Praha',
                '120 00',
                'CZ',
            ],
        );
        $this->createAccount(
            $office,
            [
                'number' => '217654320',
                'bank_code' => '0300',
                'iban' => 'CZ3203000000000217654320',
                'swift' => 'CEKOCZPP',
                'bank_name' => 'ČSOB',
            ],
            true,
        );

        $telecom = $this->createParty(
            Contact::class,
            $user,
            [
                'name' => 'Netcom Telekomunikace a.s.',
                'company_number' => $this->companyNumber($user, 21),
                'vat_number' => 'CZ' . $this->companyNumber($user, 21),
                'email' => 'fakturace' . $user->id . '@dev-netcom.test',
            ],
            [
                'Na Pankráci 1724',
                'Praha',
                '140 00',
                'CZ',
            ],
        );
        $this->createAccount(
            $telecom,
            [
                'number' => '1029384751',
                'bank_code' => '5500',
                'iban' => 'CZ2355000000001029384751',
                'swift' => 'RZBCCZPP',
                'bank_name' => 'Raiffeisenbank',
            ],
            true,
        );

        return [$office, $telecom];
    }

    /**
     * @template TParty of Business|Contact
     *
     * @param  class-string<TParty>  $model
     * @param  array<string, mixed>  $attributes
     * @param  array{string, string, string, string}  $address  street, city, postal code, country
     * @return TParty
     */
    private function createParty(
        string $model,
        User $user,
        array $attributes,
        array $address,
    ): Business|Contact {
        $partyQuery = $model::query();
        $party = $partyQuery->create(
            [
                'user_id' => $user->id,
                'registration_country' => 'CZ',
                ...$attributes,
            ],
        );

        [
            $street,
            $city,
            $postalCode,
            $country,
        ] = $address;
        $addressRelation = $party->address();
        $addressRelation->create(
            [
                'street' => $street,
                'city' => $city,
                'postal_code' => $postalCode,
                'country' => $country,
            ],
        );

        return $party;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function createAccount(
        Business|Contact $owner,
        array $attributes,
        bool $default,
    ): void {
        $bankAccounts = $owner->bankAccounts();
        $account = $bankAccounts->create(['country' => 'CZ', ...$attributes]);
        if ($default) {
            $owner->forceFill(['default_bank_account_id' => $account->id]);
            $owner->save();
        }
    }

    /**
     * @return non-empty-list<InvoiceProductWriteData> a line of one of each product, which invoices are built from
     */
    private function createProducts(User $user): array
    {
        $catalog = [];
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

        foreach (
            $products as $index => [
                $name,
                $unit,
                $price,
                $rate,
            ]
        ) {
            $productQuery = Product::query();
            $product = $productQuery->create(
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'slug' => 'dev-' . $user->id . '-' . ($index + 1),
                    'price' => $price,
                    'currency' => Currency::CZK->value,
                    'unit' => $unit->value,
                    'tax_id' => $this->taxId($rate),
                    'is_default' => $index === 0,
                ],
            );
            $catalog[] = new InvoiceProductWriteData(
                name: $name,
                quantity: '1',
                unit: $unit,
                price: Money::fromString($price, Currency::CZK),
                taxRate: $rate,
                productId: ProductId::fromInt($product->id),
            );
        }

        return $catalog;
    }

    /**
     * One to three invoices a month from the default business, numbered per year in issue order: the first
     * two to a one-off customer that is not in the contacts, the others to the customers.
     *
     * @param  non-empty-list<Contact>  $customers
     * @param  non-empty-list<InvoiceProductWriteData>  $catalog
     */
    private function createInvoices(
        User $user,
        Business $business,
        array $customers,
        array $catalog,
    ): void {
        $today = new DateTimeImmutable('today');
        $oneOffCustomers = [
            [
                'customer_name' => 'Jan Novák',
                'customer_street' => 'Lipová 14',
                'customer_city' => 'Liberec',
                'customer_postal_code' => '460 01',
                'customer_country' => 'CZ',
            ],
            [
                'customer_name' => 'Kavárna Na Rohu',
                'customer_street' => 'Pražská 31',
                'customer_city' => 'Hradec Králové',
                'customer_postal_code' => '500 02',
                'customer_country' => 'CZ',
            ],
        ];
        $methodQuery = DB::table('payment_methods');
        $methodQuery->where('slug', PaymentMethodCode::BANK_ACCOUNT->value);
        $bankTransfer = $methodQuery->value('id');
        if ($bankTransfer === null) {
            throw new RuntimeException(
                'Payment method ' . PaymentMethodCode::BANK_ACCOUNT->value . ' is missing; run ReferenceDataSeeder first',
            );
        }
        $contactCustomers = [];
        foreach ($customers as $contact) {
            $contactCustomers[] = [
                'contact_id' => $contact->id,
                ...$this->documentParty('customer', $contact),
            ];
        }
        $accountQuery = BankAccount::query();
        $account = $accountQuery->findOrFail($business->default_bank_account_id);
        $supplier = [
            'business_id' => $business->id,
            'bank_account_id' => $account->id,
            ...$this->documentParty('supplier', $business),
            ...$this->documentAccount($account),
        ];
        $invoiceMapper = new EloquentInvoiceMapper;
        $invoiceQuery = Invoice::query();
        $lineQuery = InvoiceProduct::query();
        $sequences = [];

        for ($monthsBack = self::MONTHS - 1; $monthsBack >= 0; $monthsBack--) {
            $month = $today->modify('first day of this month');
            $month = $month->modify('-' . $monthsBack . ' months');
            $days = [];
            for ($i = $this->random->getInt(1, 3); $i > 0; $i--) {
                $days[] = $this->random->getInt(0, 27);
            }
            sort($days);

            foreach ($days as $day) {
                $date = $month->modify('+' . $day . ' days');
                if ($date > $today) {
                    continue;
                }

                $year = (int) $date->format('Y');
                $sequences[$year] = ($sequences[$year] ?? 0) + 1;
                $number = InvoiceNumber::fromYearAndSequence($year, $sequences[$year]);
                $lines = [];
                for ($i = $this->random->getInt(1, 3); $i > 0; $i--) {
                    $product = $catalog[$this->random->getInt(
                        0,
                        count($catalog) - 1,
                    )];
                    $lines[] = new InvoiceProductWriteData(
                        name: $product->name,
                        quantity: (string) $this->random->getInt(1, 10),
                        unit: $product->unit,
                        price: $product->price,
                        taxRate: $product->taxRate,
                        productId: $product->productId,
                    );
                }
                $totals = InvoiceTotals::fromProducts(Currency::CZK, $lines);
                $total = $totals->total();
                $customer = array_shift($oneOffCustomers);
                if ($customer === null) {
                    $customer = $contactCustomers[$this->random->getInt(
                        0,
                        count($contactCustomers) - 1,
                    )];
                }
                $status = $this->invoiceStatus($date, $today);

                // Created when issued, so the seeded invoices do not use up the current month's quota
                $invoice = $invoiceQuery->create(
                    [
                        'user_id' => $user->id,
                        ...$supplier,
                        ...$customer,
                        'payment_method_id' => $bankTransfer,
                        'invoice_vs' => $number->value,
                        'issue_date' => $date->format('Y-m-d'),
                        'tax_point_date' => $date->format('Y-m-d'),
                        'due_in' => self::DUE_IN_DAYS,
                        'payment_status' => $status->value,
                        'payment_amount' => $total->amount,
                        'payment_currency' => Currency::CZK->value,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ],
                );
                foreach ($lines as $line) {
                    $lineQuery->create(
                        [
                            'invoice_id' => $invoice->id,
                            ...$invoiceMapper->toLineAttributes($line),
                        ],
                    );
                }
            }
        }
    }

    /**
     * Unpaid invoices past their due date show as overdue.
     */
    private function invoiceStatus(DateTimeImmutable $issueDate, DateTimeImmutable $today): InvoiceStatus
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, self::DUE_IN_DAYS);
        $roll = $this->random->getInt(1, 100);

        if ($dueDate->date() >= $today) {
            return $roll <= 30 ? InvoiceStatus::PAID : InvoiceStatus::PENDING;
        }

        return match (true) {
            $roll <= 75 => InvoiceStatus::PAID,
            $roll <= 95 => InvoiceStatus::PENDING,
            default => InvoiceStatus::CANCELLED,
        };
    }

    /**
     * Two to four expenses a month, paid to one of the supplier contacts from its account or to a one-off
     * supplier that is not in the contacts.
     *
     * @param  list<Contact>  $suppliers
     */
    private function createExpenses(
        User $user,
        Business $business,
        array $suppliers,
    ): void {
        $today = new DateTimeImmutable('today');
        $categories = $this->referenceIds('expense_categories');
        $paidStatus = $this->statusId('expense-statuses', 'paid-expense');
        $customer = $this->documentParty('customer', $business);
        $descriptions = [
            'Nájem kanceláře',
            'Software a licence',
            'Telefon a internet',
            'Cestovné',
            'Kancelářské potřeby',
            'Reklama',
        ];
        $oneOffSuppliers = [
            [
                'Papírnictví U Mostu s.r.o.',
                'Mostecká 9',
                'Praha',
                '118 00',
            ],
            [
                'Cestovní kancelář Horizont s.r.o.',
                'Jánská 2',
                'Brno',
                '602 00',
            ],
            [
                'Tiskárna Rychlotisk',
                'Palackého 15',
                'Olomouc',
                '779 00',
            ],
        ];

        for ($monthsBack = self::MONTHS - 1; $monthsBack >= 0; $monthsBack--) {
            $month = $today->modify('first day of this month');
            $month = $month->modify('-' . $monthsBack . ' months');
            for ($i = $this->random->getInt(2, 4); $i > 0; $i--) {
                $date = $month->modify(
                    '+' . $this->random->getInt(0, 27) . ' days',
                );
                if ($date > $today) {
                    continue;
                }

                if ($this->random->getInt(1, 100) <= 60) {
                    $contact = $suppliers[$this->random->getInt(
                        0,
                        count($suppliers) - 1,
                    )];
                    $accountQuery = BankAccount::query();
                    $account = $accountQuery->findOrFail($contact->default_bank_account_id);
                    $supplier = [
                        'contact_id' => $contact->id,
                        'bank_account_id' => $account->id,
                        ...$this->documentParty('supplier', $contact),
                        ...$this->documentAccount($account),
                    ];
                } else {
                    [
                        $name,
                        $street,
                        $city,
                        $postalCode,
                    ] = $oneOffSuppliers[$this->random->getInt(
                        0,
                        count($oneOffSuppliers) - 1,
                    )];
                    $supplier = [
                        'supplier_name' => $name,
                        'supplier_street' => $street,
                        'supplier_city' => $city,
                        'supplier_postal_code' => $postalCode,
                        'supplier_country' => 'CZ',
                    ];
                }

                $amount = $this->random->getInt(800, 18000);
                $expenseQuery = Expense::query();
                // Created on its date, so the seeded expenses do not use up the current month's quota
                $expenseQuery->create(
                    [
                        'user_id' => $user->id,
                        'business_id' => $business->id,
                        ...$customer,
                        ...$supplier,
                        'category_id' => $categories[$this->random->getInt(
                            0,
                            count($categories) - 1,
                        )],
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
                        'description' => $descriptions[$this->random->getInt(
                            0,
                            count($descriptions) - 1,
                        )],
                        'status_id' => $paidStatus,
                        'attachments' => [],
                        'created_at' => $date,
                        'updated_at' => $date,
                    ],
                );
            }
        }
    }

    /**
     * The party data a document keeps; short name, note and contact person stay in the address book.
     *
     * @return array<string, string|null>
     */
    private function documentParty(string $role, Business|Contact $party): array
    {
        $addressRelation = $party->address();
        $address = $addressRelation->firstOrFail();

        return [
            $role . '_name' => $party->name,
            $role . '_company_number' => $party->company_number,
            $role . '_registration_country' => $party->registration_country,
            $role . '_vat_number' => $party->vat_number,
            $role . '_street' => $address->street,
            $role . '_city' => $address->city,
            $role . '_postal_code' => $address->postal_code,
            $role . '_country' => $address->country,
            $role . '_email' => $party->email,
            $role . '_phone' => $party->phone,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function documentAccount(BankAccount $account): array
    {
        return [
            'account_iban' => $account->iban,
            'account_number' => $account->number,
            'account_bank_code' => $account->bank_code,
            'account_country' => $account->country,
            'account_swift' => $account->swift,
            'account_bank_name' => $account->bank_name,
        ];
    }

    /**
     * A valid IČO from the account's own range, the same on every run.
     */
    private function companyNumber(User $user, int $offset): string
    {
        return $this->czechCompanyNumber(1_000_000 + $user->id * 100 + $offset);
    }

    /**
     * @return non-empty-list<int>
     */
    private function referenceIds(string $table): array
    {
        $query = DB::table($table);
        $ids = $query->pluck('id');
        $ids = $ids->map(fn (mixed $id): int => (int) $id);
        $ids = $ids->all();
        if ($ids === []) {
            throw new RuntimeException(
                'Table ' . $table . ' is empty; run ReferenceDataSeeder first',
            );
        }

        return array_values($ids);
    }

    private function taxId(string $rate): int
    {
        $query = DB::table('taxes');
        $query->where('rate', $rate);
        $id = $query->value('id');
        if ($id === null) {
            throw new RuntimeException(
                'Tax rate ' . $rate . ' % is missing; run ReferenceDataSeeder first',
            );
        }

        return (int) $id;
    }

    private function statusId(string $category, string $slug): int
    {
        $query = DB::table('statuses');
        $query->join(
            'status_categories',
            'statuses.category_id',
            '=',
            'status_categories.id',
        );
        $query->where('status_categories.slug', $category);
        $query->where('statuses.slug', $slug);
        $id = $query->value('statuses.id');
        if ($id === null) {
            throw new RuntimeException(
                'Status ' . $category . '/' . $slug . ' is missing; run ReferenceDataSeeder first',
            );
        }

        return (int) $id;
    }
}
