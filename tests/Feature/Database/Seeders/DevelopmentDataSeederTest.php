<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class DevelopmentDataSeederTest extends TestCase
{
    #[Test]
    public function creates_every_account_with_its_role_and_the_shared_password(): void
    {
        $this->seed(DevelopmentDataSeeder::class);

        foreach (DevelopmentDataSeeder::ACCOUNTS as $email => $account) {
            $userQuery = User::where('email', $email);
            $user = $userQuery->firstOrFail();
            $this->assertTrue(
                Hash::check(DevelopmentDataSeeder::PASSWORD, $user->password),
                $email,
            );
            $this->assertTrue(
                $user->hasRole($account['role'], $account['guard']),
                $email,
            );
        }
    }

    #[Test]
    public function gives_every_frontend_account_businesses_contacts_and_expenses_within_its_limits(): void
    {
        $this->seed(DevelopmentDataSeeder::class);

        foreach (DevelopmentDataSeeder::ACCOUNTS as $email => $account) {
            $userQuery = User::where('email', $email);
            $user = $userQuery->firstOrFail();
            $businesses = Business::where('user_id', $user->id);

            if ($account['guard'] === PermissionCatalog::BACKPACK) {
                $this->assertFalse($businesses->exists(), $email);
                $expenseQuery = Expense::where('user_id', $user->id);
                $this->assertSame(
                    0,
                    $expenseQuery->count(),
                    $email,
                );

                continue;
            }

            $default = Business::findOrFail($user->default_business_id);
            $this->assertNull($default->archived_at, $email);
            $this->assertNotNull($default->default_bank_account_id, $email);
            $businessQuery = Business::where('user_id', $user->id);
            $businessQuery->whereNull('archived_at');
            $this->assertSame(
                2,
                $businessQuery->count(),
                $email,
            );
            $businessQuery = Business::where('user_id', $user->id);
            $businessQuery->whereNotNull('archived_at');
            $this->assertTrue(
                $businessQuery->exists(),
                $email,
            );
            $contactQuery = Contact::where('user_id', $user->id);
            $contactQuery->whereNull('archived_at');
            $this->assertLessThanOrEqual(
                16,
                $contactQuery->count(),
                $email,
            );
            $contactQuery = Contact::where('user_id', $user->id);
            $contactQuery->whereNotNull('archived_at');
            $this->assertTrue(
                $contactQuery->exists(),
                $email,
            );

            $expenseQuery = Expense::where('user_id', $user->id);
            $expenses = $expenseQuery->get();
            $this->assertTrue(
                $expenses->every(
                    fn (Expense $expense): bool => $expense->business_id === $default->id,
                ),
                $email,
            );
            $this->assertTrue(
                $expenses->contains(
                    fn (Expense $expense): bool => $expense->contact_id !== null && $expense->bank_account_id !== null,
                ),
                $email,
            );
            $this->assertTrue(
                $expenses->contains(fn (Expense $expense): bool => $expense->contact_id === null),
                $email,
            );
            // Dated when paid, so the seeded expenses leave the current month's quota free
            $this->assertFalse(
                $expenses->contains(
                    fn (Expense $expense): bool => $expense->created_at->greaterThan($expense->expense_date->endOfDay()),
                ),
                $email,
            );
        }
    }

    #[Test]
    public function gives_every_frontend_account_numbered_invoices_whose_lines_sum_to_their_total(): void
    {
        $this->seed(DevelopmentDataSeeder::class);
        $invoiceMapper = app(EloquentInvoiceMapper::class);

        foreach (DevelopmentDataSeeder::ACCOUNTS as $email => $account) {
            $userQuery = User::where('email', $email);
            $user = $userQuery->firstOrFail();
            $invoiceQuery = Invoice::where('user_id', $user->id);
            $invoices = $invoiceQuery->get();

            if ($account['guard'] === PermissionCatalog::BACKPACK) {
                $this->assertCount(
                    0,
                    $invoices,
                    $email,
                );

                continue;
            }

            $this->assertNotEmpty($invoices, $email);
            $numbers = $invoices->map(
                fn (Invoice $invoice): string => $invoice->invoice_vs,
            );
            $uniqueNumbers = $numbers->unique();
            $this->assertSame(
                $numbers->count(),
                $uniqueNumbers->count(),
                $email,
            );
            $oneOff = $invoices->whereNull('contact_id');
            $this->assertCount(
                2,
                $oneOff,
                $email,
            );
            foreach ($invoices as $invoice) {
                $this->assertSame(
                    $invoice->issue_date->format('Y'),
                    substr(
                        $invoice->invoice_vs,
                        0,
                        4,
                    ),
                    $email,
                );
                $invoice->load('invoiceProducts');
                $this->assertTrue($invoice->invoiceProducts->isNotEmpty(), $email);
                $dto = $invoiceMapper->toDto($invoice);
                $totals = $dto->totals;
                $total = $totals->total();
                $this->assertTrue(
                    $total->equals($dto->totalAmount),
                    $email,
                );
            }
            // Dated when issued, so the seeded invoices leave the current month's quota free
            $this->assertFalse(
                $invoices->contains(
                    fn (Invoice $invoice): bool => $invoice->created_at->greaterThan($invoice->issue_date->endOfDay()),
                ),
                $email,
            );
        }
    }

    #[Test]
    public function running_it_again_recreates_the_same_data(): void
    {
        $this->seed(DevelopmentDataSeeder::class);
        $userQuery = User::whereIn(
            'email',
            array_keys(DevelopmentDataSeeder::ACCOUNTS),
        );
        $users = $userQuery->pluck('id');
        $first = $this->seededData($users->all());

        $this->seed(DevelopmentDataSeeder::class);

        $userQuery = User::whereIn(
            'email',
            array_keys(DevelopmentDataSeeder::ACCOUNTS),
        );
        $this->assertSame(
            count(DevelopmentDataSeeder::ACCOUNTS),
            $userQuery->count(),
        );
        $this->assertSame(
            $first,
            $this->seededData($users->all()),
        );
    }

    #[Test]
    public function refuses_to_run_outside_local_development(): void
    {
        $this->app['env'] = 'staging';

        $this->expectException(RuntimeException::class);

        $seeder = $this->app->make(DevelopmentDataSeeder::class);
        $seeder->run();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, list<mixed>>
     */
    private function seededData(array $userIds): array
    {
        $businessQuery = Business::whereIn('user_id', $userIds);
        $businessQuery->orderBy('name');
        $businessValues = $businessQuery->pluck('name');
        $contactQuery = Contact::whereIn('user_id', $userIds);
        $contactQuery->orderBy('name');
        $contactValues = $contactQuery->pluck('name');
        $expenseQuery = Expense::whereIn('user_id', $userIds);
        $expenseQuery->orderBy('expense_date');
        $expenseQuery->orderBy('amount');
        $expenseValues = $expenseQuery->pluck('amount');
        $invoiceQuery = Invoice::whereIn('user_id', $userIds);
        $invoiceQuery->orderBy('user_id');
        $invoiceQuery->orderBy('invoice_vs');
        $invoiceValues = $invoiceQuery->pluck('payment_amount');

        return [
            'businesses' => $businessValues->all(),
            'contacts' => $contactValues->all(),
            'expenses' => $expenseValues->all(),
            'invoices' => $invoiceValues->all(),
        ];
    }
}
