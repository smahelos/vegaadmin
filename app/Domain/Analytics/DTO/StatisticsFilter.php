<?php

declare(strict_types=1);

namespace App\Domain\Analytics\DTO;

use App\Domain\Analytics\Enums\StatisticsPeriod;
use App\Domain\Party\ValueObjects\ContactId;
use DateTimeImmutable;

final readonly class StatisticsFilter
{
    /**
     * @param  list<ContactId>  $contactIds  empty means all customers
     */
    public function __construct(
        public StatisticsPeriod $period,
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
        public array $contactIds,
    ) {}

    /**
     * An explicit date range wins over the period.
     */
    public function start(DateTimeImmutable $now): DateTimeImmutable
    {
        return $this->from ?? $this->period->start($now);
    }
}
