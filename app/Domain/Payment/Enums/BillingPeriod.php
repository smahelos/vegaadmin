<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

use DateTimeImmutable;

/**
 * How often a subscription plan is billed; a plan's billing interval counts these periods.
 */
enum BillingPeriod: string
{
    case MONTHLY = 'monthly';
    case YEARLY = 'yearly';

    /**
     * The end of $intervals periods from $start. A day the target month lacks becomes
     * its last day (31 Jan + 1 month = 28 Feb), so a paid period never spills into the next month.
     */
    public function end(DateTimeImmutable $start, int $intervals): DateTimeImmutable
    {
        $months = $intervals;
        if ($this === self::YEARLY) {
            $months = $intervals * 12;
        }

        $monthStart = $start->modify('first day of this month');
        $targetMonth = $monthStart->modify('+' . $months . ' months');
        $day = min(
            (int) $start->format('j'),
            (int) $targetMonth->format('t'),
        );

        return $targetMonth->setDate(
            (int) $targetMonth->format('Y'),
            (int) $targetMonth->format('n'),
            $day,
        );
    }
}
