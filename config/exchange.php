<?php

return [
    // Number of consecutive failures required to open the circuit.
    'fail_threshold' => env('FX_FAIL_THRESHOLD', 3),

    // Seconds the circuit stays open (short-circuiting external calls) before allowing a new probe.
    'open_timeout_seconds' => env('FX_OPEN_TIMEOUT', 60),

    // Maximum retry attempts for a single external fetch while circuit is closed.
    'max_retry_attempts' => env('FX_MAX_RETRY_ATTEMPTS', 3),

    // Base delay (ms) between retries (ignored in testing environment to keep tests fast).
    'retry_base_delay_ms' => env('FX_RETRY_BASE_DELAY_MS', 100),

    // Jitter percentage (0-100) to randomize retry delay (production only).
    'retry_jitter_percent' => env('FX_RETRY_JITTER_PCT', 20),

    // Skip retry sleep between attempts (useful for speeding up automated tests). Set true in testing.
    'skip_retry_sleep' => env('FX_SKIP_RETRY_SLEEP', false),

    // Enable deterministic in-memory FX service (used in tests or local dev) instead of HTTP based service.
    'deterministic_enabled' => env('FX_DETERMINISTIC', false),

    // Optional deterministic custom rates (comma separated CODE:RATE pairs, e.g. "USD:1,EUR:0.82,CZK:22").
    'deterministic_rates' => env('FX_DETERMINISTIC_RATES', null),

    // Log a warning for each failed attempt (noisy; keep false in tests).
    'log_attempt_warnings' => env('FX_LOG_ATTEMPT_WARNINGS', false),
];
