<?php

namespace App\Infrastructure\Shared\Log;

use App\Domain\Shared\Log\Contracts\LogInterface;
use Illuminate\Support\Facades\Log as LogFacade;

/**
 * Adapter to bridge Domain's LogInterface with Laravel's logger.
 */
class LaravelLogger implements LogInterface
{
    /**
     * Logs via Laravel's Log facade.
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Delegate to Laravel's logger, preserving context
        LogFacade::log($level, $message, $context);
    }
}
