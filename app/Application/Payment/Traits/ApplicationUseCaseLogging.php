<?php

namespace App\Application\Payment\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Provides lightweight structured logging with duration measurement for application service use-cases.
 */
trait ApplicationUseCaseLogging
{
    /**
     * @param string $useCase Descriptive use case name.
     * @param array $context Additional contextual key/value pairs.
     * @param callable $callback Executed use case; its return value is returned.
     * @return mixed
     */
    protected function withUseCaseLog(string $useCase, array $context, callable $callback): mixed
    {
        $start = hrtime(true);
        Log::info('usecase.start', $context + ['use_case' => $useCase]);
        try {
            $result = $callback();
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            Log::info('usecase.success', $context + ['use_case' => $useCase, 'duration_ms' => $durationMs]);
            return $result;
        } catch (\Throwable $e) {
            $durationMs = (hrtime(true) - $start) / 1_000_000;
            Log::error('usecase.error', $context + [
                'use_case' => $useCase,
                'duration_ms' => $durationMs,
                'exception' => $e->getMessage(),
            ]);
            throw $e; // propagate for controller / handler mapping
        }
    }
}
