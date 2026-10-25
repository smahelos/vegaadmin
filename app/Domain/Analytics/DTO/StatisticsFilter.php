<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Analytics\Enums\StatisticsPeriod;
use App\Domain\Party\ValueObjects\PartyId;
use DateTimeImmutable;

final readonly class StatisticsFilter
{
    /**
     * @param  list<PartyId>  $clientIds  empty means all clients
     */
    public function __construct(
        public StatisticsPeriod $period,
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
        public array $clientIds,
    ) {}

    /**
     * An explicit date range wins over the period.
     */
    public function start(DateTimeImmutable $now): DateTimeImmutable
    {
        return $this->from ?? $this->period->start($now);
    }
}
