<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders;

use App\Infrastructure\Authorization\PermissionCatalog;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
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
    public function gives_every_frontend_account_businesses_contacts_and_expenses_within_its_limits(): void
    {
        $this->seed(DevelopmentDataSeeder::class);

        foreach (DevelopmentDataSeeder::ACCOUNTS as $email => $account) {
            $user = User::where('email', $email)->firstOrFail();
            $businesses = Business::where('user_id', $user->id);

            if ($account['guard'] === PermissionCatalog::BACKPACK) {
                $this->assertFalse($businesses->exists(), $email);
                $this->assertSame(
                    0,
                    Expense::where('user_id', $user->id)->count(),
                    $email,
                );

                continue;
            }

            $default = Business::findOrFail($user->default_business_id);
            $this->assertNull($default->archived_at, $email);
            $this->assertNotNull($default->default_bank_account_id, $email);
            $this->assertSame(
                2,
                Business::where('user_id', $user->id)->whereNull('archived_at')->count(),
                $email,
            );
            $this->assertTrue(Business::where('user_id', $user->id)->whereNotNull('archived_at')->exists(), $email);
            $this->assertLessThanOrEqual(
                16,
                Contact::where('user_id', $user->id)->whereNull('archived_at')->count(),
                $email,
            );
            $this->assertTrue(Contact::where('user_id', $user->id)->whereNotNull('archived_at')->exists(), $email);

            $expenses = Expense::where('user_id', $user->id)->get();
            $this->assertTrue($expenses->every(fn (Expense $expense): bool => $expense->business_id === $default->id), $email);
            $this->assertTrue($expenses->contains(fn (Expense $expense): bool => $expense->contact_id !== null && $expense->bank_account_id !== null), $email);
            $this->assertTrue($expenses->contains(fn (Expense $expense): bool => $expense->contact_id === null), $email);
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
        $first = $this->seededData($users->all());

        $this->seed(DevelopmentDataSeeder::class);

        $this->assertSame(count(DevelopmentDataSeeder::ACCOUNTS), User::whereIn('email', array_keys(DevelopmentDataSeeder::ACCOUNTS))->count());
        $this->assertSame($first, $this->seededData($users->all()));
    }

    #[Test]
    public function refuses_to_run_outside_local_development(): void
    {
        $this->app['env'] = 'staging';

        $this->expectException(RuntimeException::class);

        $this->app->make(DevelopmentDataSeeder::class)->run();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<string, list<mixed>>
     */
    private function seededData(array $userIds): array
    {
        return [
            'businesses' => Business::whereIn('user_id', $userIds)->orderBy('name')->pluck('name')->all(),
            'contacts' => Contact::whereIn('user_id', $userIds)->orderBy('name')->pluck('name')->all(),
            'expenses' => Expense::whereIn('user_id', $userIds)->orderBy('expense_date')->orderBy('amount')->pluck('amount')->all(),
        ];
    }
}
