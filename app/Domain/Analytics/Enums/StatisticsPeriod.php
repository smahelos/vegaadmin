<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Enums;

use DateTimeImmutable;

enum StatisticsPeriod: string
{
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case HALF_YEAR = 'half_year';
    case YEAR = 'year';

    /**
     * Whole months only, so that the first month of a chart is never cut short.
     */
    public function start(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now
            ->modify('first day of this month')
            ->modify('-' . $this->months() . ' months')
            ->setTime(0, 0);
    }

    private function months(): int
    {
        return match ($this) {
            self::MONTH => 1,
            self::QUARTER => 3,
            self::HALF_YEAR => 6,
            self::YEAR => 12,
        };
    }
}
