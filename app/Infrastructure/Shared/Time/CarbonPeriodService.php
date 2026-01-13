<?php

namespace App\Infrastructure\Shared\Time;

use App\Domain\Shared\Time\Contracts\PeriodServiceInterface;
use Carbon\CarbonImmutable;

class CarbonPeriodService implements PeriodServiceInterface
{
    public function getRange(string $periodType, \DateTimeImmutable $reference): array
    {
        $now = CarbonImmutable::instance($reference);

        return match ($periodType) {
            'hourly' => [$now->startOfHour(), $now->endOfHour()],
            'daily' => [$now->startOfDay(), $now->endOfDay()],
            'weekly' => [$now->startOfWeek(), $now->endOfWeek()],
            'monthly' => [$now->startOfMonth(), $now->endOfMonth()],
            'yearly' => [$now->startOfYear(), $now->endOfYear()],
            'lifetime' => [CarbonImmutable::parse('2020-01-01'), CarbonImmutable::parse('2099-12-31')],
            default => [$now->startOfDay(), $now->endOfDay()],
        };
    }
}
