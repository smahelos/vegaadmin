<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Analytics\Repositories;

use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Analytics\Repositories\EloquentApiStatisticsRepository;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Status;
use App\Models\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentApiStatisticsRepositoryTest extends TestCase
{
    private EloquentApiStatisticsRepository $repository;

    private User $user;

    private Client $client;

    private Client $otherClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(EloquentApiStatisticsRepository::class);
        $this->user = User::factory()->create();
        $this->client = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Alfa']);
        $this->otherClient = Client::factory()->create(['user_id' => $this->user->id, 'name' => 'Beta']);

        $this->invoice(
            $this->client,
            '2026-01-10',
            1000,
            'paid',
        );
        $this->invoice(
            $this->client,
            '2026-01-20',
            500,
            'pending',
        );
        $this->invoice(
            $this->otherClient,
            '2026-02-05',
            300,
            'paid',
        );
        // Outside the range and another user's invoice in the range
        $this->invoice(
            $this->client,
            '2025-12-31',
            7000,
            'paid',
        );
        $this->invoice(
            Client::factory()->create(),
            '2026-01-15',
            9000,
            'paid',
        );
    }

    #[Test]
    public function monthly_revenue_sums_the_users_invoices_in_the_range_by_month(): void
    {
        $rows = $this->repository->monthlyRevenue(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-02-28'),
            [],
        );

        $this->assertSame([
            [
                'month' => '2026-01',
                'total' => 1500.0,
                'paid' => 1000.0,
            ],
            [
                'month' => '2026-02',
                'total' => 300.0,
                'paid' => 300.0,
            ],
        ], $this->numeric($rows, ['total', 'paid']));
    }

    #[Test]
    public function client_filter_limits_every_invoice_query(): void
    {
        $rows = $this->repository->revenue(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            null,
            [PartyId::fromInt($this->otherClient->id)],
        );

        $this->assertSame([
            [
                'month' => '2026-02',
                'amount' => 300.0,
                'type' => 'revenue',
            ],
        ], $this->numeric($rows, ['amount']));
    }

    #[Test]
    public function client_revenue_ranks_clients_by_total(): void
    {
        $rows = $this->repository->clientRevenue(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            null,
            [],
        );

        $this->assertSame([
            [
                'client_id' => $this->client->id,
                'client_name' => 'Alfa',
                'total' => 1500.0,
            ],
            [
                'client_id' => $this->otherClient->id,
                'client_name' => 'Beta',
                'total' => 300.0,
            ],
        ], $this->numeric($rows, ['total']));
    }

    #[Test]
    public function invoice_status_counts_invoices_per_status(): void
    {
        $rows = $this->repository->invoiceStatus(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            null,
            [],
        );

        $counts = array_column(
            $rows,
            'count',
            'status',
        );
        ksort($counts);

        $this->assertSame(['paid' => 2, 'pending' => 1], array_map('intval', $counts));
    }

    #[Test]
    public function payment_methods_sum_invoices_per_method(): void
    {
        $rows = $this->repository->paymentMethods(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            null,
            [],
        );

        $this->assertCount(1, $rows);
        $this->assertSame('bankaccount', $rows[0]['method']);
        $this->assertSame(1800.0, (float) $rows[0]['total']);
        $this->assertSame(3, (int) $rows[0]['count']);
    }

    #[Test]
    public function expenses_sum_the_users_expenses_in_the_range_by_month(): void
    {
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_date' => '2026-01-03',
            'amount' => 120,
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_date' => '2026-01-28',
            'amount' => 80,
        ]);
        Expense::factory()->create([
            'user_id' => $this->user->id,
            'expense_date' => '2026-03-01',
            'amount' => 50,
        ]);
        Expense::factory()->create(['expense_date' => '2026-01-10', 'amount' => 999]);

        $rows = $this->repository->expenses(
            UserId::fromInt($this->user->id),
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-02-28'),
        );

        $this->assertSame([
            [
                'month' => '2026-01',
                'amount' => 200.0,
                'type' => 'expense',
            ],
        ], $this->numeric($rows, ['amount']));
    }

    private function invoice(
        Client $client,
        string $issueDate,
        int $amount,
        string $status,
    ): void {
        Invoice::factory()->create([
            'user_id' => $client->user_id,
            'client_id' => $client->id,
            'issue_date' => $issueDate,
            'payment_amount' => $amount,
            'payment_status_id' => Status::where('slug', $status)->value('id'),
            'payment_method_id' => PaymentMethod::where('slug', 'bankaccount')->value('id'),
        ]);
    }

    /**
     * MySQL returns sums as decimal strings; compare them as numbers.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function numeric(array $rows, array $columns): array
    {
        return array_map(static function (array $row) use ($columns): array {
            foreach ($columns as $column) {
                $row[$column] = (float) $row[$column];
            }

            return $row;
        }, $rows);
    }
}
