<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\StatusCategory;
use App\Models\Tax;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reference data is not seeded yet (docs/TECH_DEBT.md), so the test provides what the seeder looks up
        $invoiceStatuses = StatusCategory::factory()->create(['slug' => 'invoice-statuses']);
        foreach ([
            'paid',
            'pending',
            'overdue',
            'partially-paid',
            'cancelled',
        ] as $slug) {
            Status::factory()->create([
                'slug' => $slug,
                'category_id' => $invoiceStatuses->id,
            ]);
        }
        Status::factory()->create([
            'slug' => 'paid-expense',
            'category_id' => StatusCategory::factory()->create(['slug' => 'expense-statuses'])->id,
        ]);
        Tax::factory()->create(['rate' => '21.00']);
        Tax::factory()->create(['rate' => '12.00']);
        PaymentMethod::factory()->create();
        ExpenseCategory::factory()->create();
    }

    #[Test]
    public function creates_a_demo_user_who_signs_in_with_the_demo_password(): void
    {
        $this->seed(DemoDataSeeder::class);

        $user = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();
        $this->assertTrue(Hash::check('backpack', $user->password));
        $this->assertTrue($user->hasRole('frontend_user_plus', 'web'));
    }

    #[Test]
    public function fills_the_last_year_with_invoices_and_expenses_without_counting_them_against_limits(): void
    {
        $this->seed(DemoDataSeeder::class);

        $user = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();
        $invoices = Invoice::where('user_id', $user->id)->get();
        $months = $invoices->map(fn (Invoice $invoice): string => substr((string) $invoice->issue_date, 0, 7))->unique();

        $this->assertGreaterThanOrEqual(12, $months->count());
        $this->assertSame($invoices->count(), $invoices->pluck('invoice_vs')->unique()->count());
        $this->assertTrue($invoices->every(fn (Invoice $invoice): bool => (float) $invoice->payment_amount > 0));
        $this->assertGreaterThan(0, Expense::where('user_id', $user->id)->count());
        $this->assertSame(0, DB::table('entity_limit_usage')->where('user_id', $user->id)->count());
    }

    #[Test]
    public function running_it_again_recreates_the_same_data(): void
    {
        $this->seed(DemoDataSeeder::class);
        $user = User::where('email', DemoDataSeeder::EMAIL)->firstOrFail();
        $firstNumbers = Invoice::where('user_id', $user->id)->orderBy('invoice_vs')->pluck('invoice_vs')->all();
        $firstExpenses = Expense::where('user_id', $user->id)->count();

        $this->seed(DemoDataSeeder::class);

        $this->assertSame(1, User::where('email', DemoDataSeeder::EMAIL)->count());
        $this->assertSame($firstNumbers, Invoice::where('user_id', $user->id)->orderBy('invoice_vs')->pluck('invoice_vs')->all());
        $this->assertSame($firstExpenses, Expense::where('user_id', $user->id)->count());
    }

    #[Test]
    public function refuses_to_run_outside_local_development(): void
    {
        $this->app['env'] = 'staging';

        $this->expectException(RuntimeException::class);

        $this->app->make(DemoDataSeeder::class)->run();
    }
}
