<?php

return [
    // Directories containing legacy code we freeze (no new files allowed beyond allowlist)
    'forbidden_paths' => [
        [
            'path' => base_path('app/Services'),
            'allowed_files' => [
                'ArtisanCommandsService.php',
                'CacheService.php',
                'CountryService.php',
                'CurrencyService.php',
                'InvoiceProductSyncService.php',
                'LocaleService.php',
            ],
            'allowed_directories' => [
                'PaymentGateways',
                'QrPayment',
            ],
            'mode' => 'no_new_files',
            // Prevent adding arbitrary non-namespaced global helpers into BackpackHelpers file
            [
                'pattern' => '/function\s+(?!backpack_(auth|guard_name|user|url|pro)\b)[a-zA-Z0-9_]+\s*\(/',
                'description' => 'Only backpack_* helper functions are allowed in BackpackHelpers.php',
                'paths' => [base_path('app/Helpers/BackpackHelpers.php')],
            ],
        ],
        // Root Repositories directory must remain absent (replaced by domain repositories)
        [
            'path' => base_path('app/Repositories'),
            'allowed_files' => [],
            'allowed_directories' => [],
            'mode' => 'must_not_exist', // custom mode handled in command
        ],
        // Root Traits directory was removed after domain trait relocation (Decision D-034)
        // Guard against re-introduction of legacy root trait namespace.
        [
            'path' => base_path('app/Traits'),
            'allowed_files' => [],
            'allowed_directories' => [],
            'mode' => 'must_not_exist',
        ],
        // Root Livewire components now organized under domain subdirectories (Party/, Invoice/, Product/)
        // Prevent adding new direct children .php components under app/Livewire (except domain folders)
        [
            'path' => base_path('app/Livewire'),
            'allowed_files' => [
                // Allow only non-component infrastructural files if any appear later (kept empty for strictness)
            ],
            'allowed_directories' => [
                'Party', 'Invoice', 'Product'
            ],
            'mode' => 'no_new_files',
        ],
        // Root Observers directory removed after consolidating observers into domain namespaces
        // Guard against re-introduction of legacy empty observer stubs.
        [
            'path' => base_path('app/Observers'),
            'allowed_files' => [],
            'allowed_directories' => [],
            'mode' => 'must_not_exist',
        ],
    ],

    // Regex patterns forbidding usage of legacy services outside defined allowlist
    'forbidden_patterns' => [
        [
            'pattern' => '/new\\s+App\\\\Services\\\\(?!CurrencyService|CountryService|CacheService|ArtisanCommandsService|InvoiceProductSyncService|LocaleService)[A-Z][A-Za-z0-9_]+/',
            'description' => 'Direct instantiation of legacy service outside allowlist',
        ],
        [
            'pattern' => '/app\(\)\s*->\s*make\(\s*[\'\"]App\\\\Services\\\\(?!CurrencyService|CountryService|CacheService|ArtisanCommandsService|InvoiceProductSyncService|LocaleService)[A-Z][A-Za-z0-9_]+/',
            'description' => 'Service locator resolving legacy service outside allowlist',
        ],
        [
            'pattern' => '/App\\\\Services\\\\(?!CurrencyService|CountryService|CacheService|ArtisanCommandsService|InvoiceProductSyncService|LocaleService)[A-Z][A-Za-z0-9_]+::/',
            'description' => 'Static call on legacy service outside allowlist',
        ],
        // Direct instantiation of Domain services inside Controllers (should inject interface instead)
        [
            'pattern' => '/new\s+\\?App\\\\Domain\\\\[A-Z][A-Za-z0-9\\\\]+\\\\Services\\\\[A-Z][A-Za-z0-9]+Service\s*\(/',
            'description' => 'Direct instantiation of domain service (use interface + DI)',
            // Limit scanning only to Controllers to reduce false positives in tests or internal factories
            'paths' => [base_path('app/Http/Controllers')],
        ],
    ],

    // Paths excluded from scanning
    'exclude_paths' => [
        'vendor',
        'storage',
        'bootstrap/cache',
        'node_modules',
        'public/build',
    ],

    // === Dependency Injection (DI) Sniff – preparatory configuration ===
    // Directories to scan for concrete service injection (Phase 1 regex lint)
    'di_scan_paths' => [
        base_path('app/Http/Controllers'),
        base_path('app/Console/Commands'),
        base_path('app/Policies'),
    ],
    // Namespace prefix considered a "service" (suffix heuristic applied separately)
    'di_service_namespace_prefix' => 'App\\Domain\\',
    // Suffix that marks a concrete service class
    'di_service_suffix' => 'Service',
    // Interface suffix – used to compute expected interface name (e.g. FooServiceInterface)
    'di_interface_suffix' => 'Interface',
    // Whether DI sniff is enabled (set true after implementing Phase 1)
    'di_enabled' => true,
    // Treat missing interface as advisory (true) or hard error (false)
    'di_missing_interface_advisory' => true,
    // Allow specific concrete services to bypass DI enforcement (fqcn list)
    'di_allowlist' => [
        // Example: App\Domain\Some\Services\TransitionalService
    ],
    // Output format default for CI (human|json) – CI pipelines can override via artisan option
    'default_output_format' => 'human',
    // Auto-detect CI provider inside command (Bitbucket: BITBUCKET_COMMIT, GitHub: GITHUB_SHA)
    'ci_autodetect' => true,
    // Fail build even on advisory items when true (overridden later via command option / env if implemented)
    'fail_on_advisory' => false,
    // JSON artifact path (if format=json) – future use in GitHub/Bitbucket artifact upload
    'json_report_path' => storage_path('app/ddd_guard_report.json'),

    // === Provider Snapshot (Domain DI mapping) ===
    'provider_snapshot' => [
        // Baseline snapshot path is version-controlled to allow CI diffing
        'path' => base_path('architecture/baselines/provider_snapshot.json'),
        // Allow expected provider moves without failing diff (array of ['contract'=>FQCN,'from'=>Prov,'to'=>Prov])
        'allowed_moves' => [],
        // When true any provider move not in allowed_moves is an error (otherwise warning)
        'strict_moves' => false,
    ],
];
