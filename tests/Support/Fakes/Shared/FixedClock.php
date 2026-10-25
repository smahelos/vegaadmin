<?php

declare(strict_types=1);

namespace Tests\Support\Fakes\Shared;

use App\Domain\Shared\Time\Contracts\ClockInterface;
use DateTimeImmutable;

final class FixedClock implements ClockInterface
{
    public function __construct(
        private readonly DateTimeImmutable $now,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
