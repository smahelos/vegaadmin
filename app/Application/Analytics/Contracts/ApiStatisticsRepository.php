<?php

declare(strict_types=1);

namespace App\Application\Analytics\Contracts;

use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Statistics of one user's invoices and expenses for the dashboard charts. Every method covers
 * invoices issued (expenses dated) from $from up to $to inclusive; $to null means up to today.
 * An empty $contactIds means all customers.
 */
interface ApiStatisticsRepository
{
    /**
     * @param  list<ContactId>  $contactIds
     * @return list<array<string, mixed>>
     */
    public function monthlyRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array;

    /**
     * The ten customers with the highest revenue.
     *
     * @param  list<ContactId>  $contactIds
     * @return list<array<string, mixed>>
     */
    public function customerRevenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array;

    /**
     * @param  list<ContactId>  $contactIds
     * @return list<array<string, mixed>>
     */
    public function revenue(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
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
     * @param  list<ContactId>  $contactIds
     * @return list<array<string, mixed>>
     */
    public function invoiceStatus(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array;

    /**
     * @param  list<ContactId>  $contactIds
     * @return list<array<string, mixed>>
     */
    public function paymentMethods(
        UserId $userId,
        DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        array $contactIds,
    ): array;
}
