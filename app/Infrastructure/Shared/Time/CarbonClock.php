<?php

namespace App\Infrastructure\Shared\Time;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use Carbon\CarbonImmutable;

class CarbonClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return CarbonImmutable::now();
    }
}
