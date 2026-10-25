<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Analytics;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Records the scope of every call and returns one fixed row per method. Stands in for the
 * MySQL-only queries until the tests run on MySQL (docs/TECH_DEBT.md).
 */
final class FakeApiStatisticsRepository implements ApiStatisticsRepository
{
    /** @var list<array{method: string, userId: UserId, from: DateTimeImmutable, to: ?DateTimeImmutable, clientIds: list<PartyId>}> */
    public array $calls = [];

    public function monthlyRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $clientIds,
        );

        return [[
            'month' => '2026-01',
            'total' => '1000.00',
            'paid' => '900.00',
        ]];
    }

    public function clientRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $clientIds,
        );

        return [[
            'client_id' => 1,
            'client_name' => 'Acme',
            'total' => '123.45',
        ]];
    }

    public function revenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $clientIds,
        );

        return [
            [
                'month' => '2026-02',
                'amount' => '700.00',
                'type' => 'revenue',
            ],
            [
                'month' => '2026-01',
                'amount' => '1000.00',
                'type' => 'revenue',
            ],
        ];
    }

    public function expenses(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            [],
        );

        return [[
            'month' => '2026-01',
            'amount' => '300.00',
            'type' => 'expense',
        ]];
    }

    public function invoiceStatus(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $clientIds,
        );

        return [[
            'status' => 'paid',
            'count' => 5,
        ]];
    }

    public function paymentMethods(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $clientIds,
        );

        return [[
            'method' => 'bank_transfer',
            'method_name' => 'Bank transfer',
            'total' => '200.00',
            'count' => 3,
        ]];
    }

    /**
     * @param  list<PartyId>  $clientIds
     */
    private function record(
        string $method,
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): void {
        $this->calls[] = [
            'method' => $method,
            'userId' => $userId,
            'from' => $from,
            'to' => $to,
            'clientIds' => $clientIds,
        ];
    }
}
