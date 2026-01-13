<?php

return [
    'circuit_breaker' => [
        // Number of consecutive failures required to open the circuit.
        'fail_threshold' => env('ANALYTICS_CB_FAIL_THRESHOLD', 3),

        // Seconds the circuit stays open (short-circuiting external calls) before allowing a new probe.
        'open_timeout_seconds' => env('ANALYTICS_CB_OPEN_TIMEOUT', 60),

        // Maximum retry attempts for a single external fetch while circuit is closed.
        'max_retry_attempts' => env('ANALYTICS_CB_MAX_RETRY_ATTEMPTS', 3),

        // Base delay (ms) between retries (ignored in testing environment to keep tests fast).
        'retry_base_delay_ms' => env('ANALYTICS_CB_RETRY_BASE_DELAY_MS', 100),

        // Jitter percentage (0-100) to randomize retry delay (production only).
        'retry_jitter_percent' => env('ANALYTICS_CB_RETRY_JITTER_PCT', 20),

        // Skip retry sleep between attempts (useful for speeding up automated tests). Set true in testing.
        'skip_retry_sleep' => env('ANALYTICS_CB_SKIP_RETRY_SLEEP', false),

        // Log a warning for each failed attempt (noisy; keep false in tests).
        'log_attempt_warnings' => env('ANALYTICS_CB_LOG_ATTEMPT_WARNINGS', false),
    ],

    // Cache configuration for analytics data
    'cache' => [
        // Default TTL for analytics cache in seconds
        'default_ttl' => env('ANALYTICS_CACHE_TTL', 600), // 10 minutes

        // TTL for monthly statistics
        'monthly_stats_ttl' => env('ANALYTICS_MONTHLY_CACHE_TTL', 1800), // 30 minutes

        // TTL for user statistics
        'user_stats_ttl' => env('ANALYTICS_USER_CACHE_TTL', 600), // 10 minutes

        // TTL for client statistics
        'client_stats_ttl' => env('ANALYTICS_CLIENT_CACHE_TTL', 900), // 15 minutes
    ],

    // External services configuration
    'external_services' => [
        // Market data provider
        'market_data' => [
            'enabled' => env('ANALYTICS_MARKET_DATA_ENABLED', false),
            'api_url' => env('ANALYTICS_MARKET_DATA_URL', 'https://api.example.com'),
            'api_key' => env('ANALYTICS_MARKET_DATA_KEY', null),
            'timeout' => env('ANALYTICS_MARKET_DATA_TIMEOUT', 30),
        ],

        // Industry benchmarks provider
        'benchmarks' => [
            'enabled' => env('ANALYTICS_BENCHMARKS_ENABLED', false),
            'api_url' => env('ANALYTICS_BENCHMARKS_URL', 'https://api.example.com'),
            'api_key' => env('ANALYTICS_BENCHMARKS_KEY', null),
            'timeout' => env('ANALYTICS_BENCHMARKS_TIMEOUT', 30),
        ],
    ],
];
