<?php

namespace App\Domain\Shared\Log\Contracts;

/**
 * Log contract.
 * Contract for Log implementation
 */
interface LogInterface
{
    /**
     * Log a message with a given level and context.
     */
    public function log(
        string $level,
        string $message,
        array $context = [],
    ): void;
}
