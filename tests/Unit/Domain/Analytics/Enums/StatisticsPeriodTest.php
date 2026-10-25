<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\Enums;

use App\Domain\Analytics\Enums\StatisticsPeriod;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatisticsPeriodTest extends TestCase
{
    /**
     * @return array<string, array{StatisticsPeriod, string}>
     */
    public static function periods(): array
    {
        return [
            'month' => [StatisticsPeriod::MONTH, '2026-09-01 00:00:00'],
            'quarter' => [StatisticsPeriod::QUARTER, '2026-07-01 00:00:00'],
            'half year' => [StatisticsPeriod::HALF_YEAR, '2026-04-01 00:00:00'],
            'year' => [StatisticsPeriod::YEAR, '2025-10-01 00:00:00'],
        ];
    }

    #[Test]
    #[DataProvider('periods')]
    public function starts_on_the_first_day_of_the_month_the_given_months_back(StatisticsPeriod $period, string $expected): void
    {
        $start = $period->start(new DateTimeImmutable('2026-10-24 15:30:00'));

        $this->assertSame($expected, $start->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function does_not_overflow_from_the_last_day_of_a_long_month(): void
    {
        $start = StatisticsPeriod::MONTH->start(new DateTimeImmutable('2026-03-31 08:00:00'));

        $this->assertSame('2026-02-01', $start->format('Y-m-d'));
    }
}
