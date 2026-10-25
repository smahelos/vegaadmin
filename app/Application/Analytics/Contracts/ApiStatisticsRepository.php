<?php

declare(strict_types=1);

namespace App\Application\Analytics\Contracts;

use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Statistics of one user's invoices and expenses for the dashboard charts. Every method covers
 * invoices issued (expenses dated) from $from up to $to inclusive; $to null means up to today.
 * An empty $clientIds means all clients.
 */
interface ApiStatisticsRepository
{
    /**
     * @param  list<PartyId>  $clientIds
     * @return list<array<string, mixed>>
     */
    public function monthlyRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array;

    /**
     * The ten clients with the highest revenue.
     *
     * @param  list<PartyId>  $clientIds
     * @return list<array<string, mixed>>
     */
    public function clientRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array;

    /**
     * @param  list<PartyId>  $clientIds
     * @return list<array<string, mixed>>
     */
    public function revenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function expenses(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
    ): array;

    /**
     * @param  list<PartyId>  $clientIds
     * @return list<array<string, mixed>>
     */
    public function invoiceStatus(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array;

    /**
     * @param  list<PartyId>  $clientIds
     * @return list<array<string, mixed>>
     */
    public function paymentMethods(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $clientIds,
    ): array;
}
