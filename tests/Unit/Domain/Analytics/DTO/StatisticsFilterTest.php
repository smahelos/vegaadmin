<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Analytics\DTO;

use App\Domain\Analytics\DTO\StatisticsFilter;
use App\Domain\Analytics\Enums\StatisticsPeriod;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatisticsFilterTest extends TestCase
{
    #[Test]
    public function starts_at_the_period_start_without_an_explicit_date(): void
    {
        $filter = new StatisticsFilter(
            period: StatisticsPeriod::QUARTER,
            from: null,
            to: null,
            clientIds: [],
        );

        $this->assertSame('2026-07-01', $filter->start(new DateTimeImmutable('2026-10-24'))->format('Y-m-d'));
    }

    #[Test]
    public function an_explicit_start_date_wins_over_the_period(): void
    {
        $filter = new StatisticsFilter(
            period: StatisticsPeriod::YEAR,
            from: new DateTimeImmutable('2026-05-15'),
            to: null,
            clientIds: [],
        );

        $this->assertSame('2026-05-15', $filter->start(new DateTimeImmutable('2026-10-24'))->format('Y-m-d'));
    }
}
