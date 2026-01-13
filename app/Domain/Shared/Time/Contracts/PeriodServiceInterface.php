<?php

namespace App\Domain\Shared\Time\Contracts;

/**
 * Period calculations abstraction (hourly/daily/weekly/monthly/yearly/lifetime).
 */
interface PeriodServiceInterface
{
    /**
     * Compute start/end for given period type based on reference time.
     *
     * @param string $periodType One of: hourly, daily, weekly, monthly, yearly, lifetime
     * @param \DateTimeImmutable $reference Reference point (usually now)
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} [start, end]
     */
    public function getRange(string $periodType, \DateTimeImmutable $reference): array;
}
