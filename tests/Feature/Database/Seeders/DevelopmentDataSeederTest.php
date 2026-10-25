<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DevelopmentDataSeeder;
use Illuminate\Support\Facades\DB;
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
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue(Hash::check(DevelopmentDataSeeder::PASSWORD, $user->password), $email);
            $this->assertTrue($user->hasRole($account['role'], $account['guard']), $email);
        }
    }

    #[Test]
    public function fills_the_last_year_of_every_frontend_account_without_counting_it_against_limits(): void
    {
        $this->seed(DevelopmentDataSeeder::class);

        foreach (DevelopmentDataSeeder::ACCOUNTS as $email => $account) {
            $user = User::where('email', $email)->firstOrFail();
            $invoices = Invoice::where('user_id', $user->id)->get();

            if ($account['guard'] === PermissionCatalog::BACKPACK) {
                $this->assertCount(
                    0,
                    $invoices,
                    $email,
                );

                continue;
            }

            $months = $invoices->map(fn (Invoice $invoice): string => substr((string) $invoice->issue_date, 0, 7))->unique();
            $this->assertGreaterThanOrEqual(
                12,
                $months->count(),
                $email,
            );
            $this->assertSame(
                $invoices->count(),
                $invoices->pluck('invoice_vs')->unique()->count(),
                $email,
            );
            $this->assertTrue($invoices->every(fn (Invoice $invoice): bool => (float) $invoice->payment_amount > 0), $email);
            $this->assertGreaterThan(
                0,
                Expense::where('user_id', $user->id)->count(),
                $email,
            );
            $this->assertSame(
                0,
                DB::table('entity_limit_usage')->where('user_id', $user->id)->count(),
                $email,
            );
        }
    }

    #[Test]
    public function running_it_again_recreates_the_same_data(): void
    {
        $this->seed(DevelopmentDataSeeder::class);
        $users = User::whereIn('email', array_keys(DevelopmentDataSeeder::ACCOUNTS))->pluck('id');
        $firstNumbers = Invoice::whereIn('user_id', $users)->orderBy('invoice_vs')->pluck('invoice_vs')->all();
        $firstExpenses = Expense::whereIn('user_id', $users)->count();

        $this->seed(DevelopmentDataSeeder::class);

        $this->assertSame(count(DevelopmentDataSeeder::ACCOUNTS), User::whereIn('email', array_keys(DevelopmentDataSeeder::ACCOUNTS))->count());
        $this->assertSame($firstNumbers, Invoice::whereIn('user_id', $users)->orderBy('invoice_vs')->pluck('invoice_vs')->all());
        $this->assertSame($firstExpenses, Expense::whereIn('user_id', $users)->count());
    }

    #[Test]
    public function refuses_to_run_outside_local_development(): void
    {
        $this->app['env'] = 'staging';

        $this->expectException(RuntimeException::class);

        $this->app->make(DevelopmentDataSeeder::class)->run();
    }
}
