<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Enums;

use App\Domain\Payment\Enums\BillingPeriod;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BillingPeriodTest extends TestCase
{
    /**
     * @return array<string, array{BillingPeriod, string, int, string}>
     */
    public static function periods(): array
    {
        return [
            'one month' => [
                BillingPeriod::MONTHLY,
                '2026-03-15 10:30:00',
                1,
                '2026-04-15 10:30:00',
            ],
            'month end clamps to a shorter month' => [
                BillingPeriod::MONTHLY,
                '2026-01-31 10:30:00',
                1,
                '2026-02-28 10:30:00',
            ],
            'several months across a year' => [
                BillingPeriod::MONTHLY,
                '2026-11-30 00:00:00',
                3,
                '2027-02-28 00:00:00',
            ],
            'one year' => [
                BillingPeriod::YEARLY,
                '2026-05-01 08:00:00',
                1,
                '2027-05-01 08:00:00',
            ],
            'leap day clamps in a common year' => [
                BillingPeriod::YEARLY,
                '2028-02-29 12:00:00',
                1,
                '2029-02-28 12:00:00',
            ],
            'several years' => [
                BillingPeriod::YEARLY,
                '2026-07-31 00:00:00',
                2,
                '2028-07-31 00:00:00',
            ],
        ];
    }

    #[Test]
    #[DataProvider('periods')]
    public function the_end_adds_whole_periods_without_overflowing_the_month(
        BillingPeriod $period,
        string $start,
        int $intervals,
        string $expected,
    ): void {
        $end = $period->end(
            new DateTimeImmutable($start),
            $intervals,
        );

        $this->assertSame(
            $expected,
            $end->format('Y-m-d H:i:s'),
        );
    }
}
