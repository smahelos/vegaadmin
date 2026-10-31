<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Analytics;

use App\Application\Analytics\Contracts\ApiStatisticsRepository;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Records the scope of every call and returns one fixed row per method, so that controller tests
 * check the scope they pass; the queries themselves are pinned by EloquentApiStatisticsRepositoryTest.
 */
final class FakeApiStatisticsRepository implements ApiStatisticsRepository
{
    /** @var list<array{method: string, userId: UserId, from: DateTimeImmutable, to: ?DateTimeImmutable, contactIds: list<ContactId>}> */
    public array $calls = [];

    public function monthlyRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $contactIds,
        );

        return [[
            'month' => '2026-01',
            'total' => '1000.00',
            'paid' => '900.00',
        ]];
    }

    public function customerRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $contactIds,
        );

        return [[
            'contact_id' => 1,
            'customer_name' => 'Acme',
            'total' => '123.45',
        ]];
    }

    public function revenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $contactIds,
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
        array $contactIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $contactIds,
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
        array $contactIds,
    ): array {
        $this->record(
            __FUNCTION__,
            $userId,
            $from,
            $to,
            $contactIds,
        );

        return [[
            'method' => 'bank_transfer',
            'method_name' => 'Bank transfer',
            'total' => '200.00',
            'count' => 3,
        ]];
    }

    /**
     * @param  list<ContactId>  $contactIds
     */
    private function record(
        string $method,
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): void {
        $this->calls[] = [
            'method' => $method,
            'userId' => $userId,
            'from' => $from,
            'to' => $to,
            'contactIds' => $contactIds,
        ];
    }
}
