<?php

namespace App\Domain\Shared\Time\Contracts;

/**
 * Simple clock abstraction for current time retrieval.
 * Keeps Domain free from specific datetime libraries.
 */
interface ClockInterface
{
    /**
     * Get current time as immutable datetime.
     */
    public function now(): \DateTimeImmutable;
}
