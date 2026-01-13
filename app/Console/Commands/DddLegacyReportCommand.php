<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Generates a structured report of residual legacy artifacts and neutralized test files.
 *
 * Goals (Phase 1 MVP):
 *  - Enumerate legacy residues (services, contracts, repositories directories etc.)
 *  - Detect neutralized test files (placeholder tests containing no assertions / only comments)
 *  - JSON output for CI consumption + optional strict exit codes
 *  - Human concise output for local developer use
 */
class DddLegacyReportCommand extends Command
{
    protected $signature = 'ddd:legacy-report
        {--json : Output JSON instead of human readable}
        {--strict : Exit with non-zero code if any residuals or neutralized tests found}
        {--max-comment-ratio=0.8 : Threshold (0-1) above which a test file is considered neutralized}
        {--write-baseline : Persist JSON output to architecture/baselines/legacy_report_latest.json}';

    protected $description = 'Report legacy residues & neutralized tests (supports CI gating)';

    public function __construct(private Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $commentRatio = (float)$this->option('max-comment-ratio');
        if ($commentRatio <= 0 || $commentRatio > 1) {
            $this->error('Invalid --max-comment-ratio (must be in (0,1]).');
            return 1;
        }

        $residuals = $this->scanResiduals();
        $neutralized = $this->scanNeutralizedTests($commentRatio);

        $payload = [
            'timestamp' => now()->toIso8601String(),
            'residuals' => $residuals,
            'neutralized_tests' => $neutralized,
            'counts' => [
                'residuals' => count($residuals),
                'neutralized_tests' => count($neutralized),
            ],
        ];

        $isJson = $this->option('json');
        $strict = $this->option('strict');

        if ($isJson) {
            $json = json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
            $this->line($json);
            if ($this->option('write-baseline')) {
                $outDir = base_path('architecture/baselines');
                if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }
                file_put_contents($outDir.'/legacy_report_latest.json', $json);
                $this->info('Legacy report baseline written to architecture/baselines/legacy_report_latest.json');
            }
        } else {
            $this->renderHuman($payload);
            if ($this->option('write-baseline')) {
                $outDir = base_path('architecture/baselines');
                if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }
                file_put_contents($outDir.'/legacy_report_latest.json', json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
                $this->info('Legacy report baseline written to architecture/baselines/legacy_report_latest.json');
            }
        }

        $hasFindings = $payload['counts']['residuals'] > 0 || $payload['counts']['neutralized_tests'] > 0;
        if ($strict && $hasFindings) {
            return 2; // distinct non-zero for easier CI rule
        }
        return 0;
    }

    /**
     * Identify residual legacy artifacts that should be removed or monitored.
     * Currently: leftover root legacy service files, contracts dir reappearance, repositories root.
     */
    private function scanResiduals(): array
    {
        $residuals = [];

        // 1. Root legacy services directory contents (non-allowlist)
        $legacyServices = base_path('app/Services');
        if (is_dir($legacyServices)) {
            $allow = [
                'ArtisanCommandsService.php',
                'CacheService.php',
                'CountryService.php',
                'CurrencyService.php',
                'InvoiceProductSyncService.php',
                'LocaleService.php',
                // Historical allowlist; remove items here once migrated fully
            ];
            foreach ($this->files->files($legacyServices) as $file) {
                $name = $file->getFilename();
                if (!in_array($name, $allow, true)) {
                    $residuals[] = [
                        'type' => 'legacy_service_file',
                        'path' => $file->getPathname(),
                        'message' => 'Unexpected file in frozen legacy services directory',
                    ];
                }
            }
        }

        // 2. Root Contracts directory (should not reappear)
        $rootContracts = base_path('app/Contracts');
        if (is_dir($rootContracts)) {
            $residuals[] = [
                'type' => 'root_contracts_directory',
                'path' => $rootContracts,
                'message' => 'Root Contracts directory must not exist (all contracts live in domain namespaces)',
            ];
        }

        // 3. Root Repositories directory (migration replaced by domain repos)
        $rootRepos = base_path('app/Repositories');
        if (is_dir($rootRepos)) {
            $residuals[] = [
                'type' => 'root_repositories_directory',
                'path' => $rootRepos,
                'message' => 'Root Repositories directory must not exist (use Domain/<Domain>/Repositories)',
            ];
        }

        // 4. Root Traits directory (post migration) – presence is a regression
        $rootTraits = base_path('app/Traits');
        if (is_dir($rootTraits)) {
            $residuals[] = [
                'type' => 'root_traits_directory',
                'path' => $rootTraits,
                'message' => 'Root Traits directory must not exist (traits relocated into domains)',
            ];
        }

        return $residuals;
    }

    /**
     * Find neutralized (effectively disabled) test files – high comment ratio + zero assertions.
     */
    private function scanNeutralizedTests(float $commentRatio): array
    {
        $neutralized = [];
        $testRoot = base_path('tests');
        if (!is_dir($testRoot)) {
            return $neutralized;
        }

        $phpFiles = collect($this->files->allFiles($testRoot))
            ->filter(fn($f) => str_ends_with($f->getFilename(), 'Test.php'));

        foreach ($phpFiles as $file) {
            $contents = $file->getContents();
            $lines = preg_split('/\r?\n/', $contents);
            if (!$lines) { continue; }
            $total = 0; $commentOrEmpty = 0; $assertions = 0;
            foreach ($lines as $line) {
                $trim = trim($line);
                if ($trim === '') { $commentOrEmpty++; $total++; continue; }
                if (str_starts_with($trim, '//') || str_starts_with($trim, '/*') || str_starts_with($trim, '*')) {
                    $commentOrEmpty++; $total++; continue; }
                $total++;
                if (preg_match('/->assert|assertEquals|assertTrue|assertFalse|assertSame|assertCount|expect\(/', $trim)) {
                    $assertions++;
                }
            }
            if ($assertions === 0 && $total > 0 && ($commentOrEmpty / $total) >= $commentRatio) {
                $neutralized[] = [
                    'path' => $file->getPathname(),
                    'comment_ratio' => round($commentOrEmpty / $total, 3),
                    'lines' => $total,
                    'message' => 'Likely neutralized placeholder (no assertions, high comment ratio)',
                ];
            }
        }
        return $neutralized;
    }

    private function renderHuman(array $payload): void
    {
        $this->info('DDD Legacy Report @ '.$payload['timestamp']);
        $this->line('Residuals: '.$payload['counts']['residuals'].' | Neutralized tests: '.$payload['counts']['neutralized_tests']);
        if ($payload['counts']['residuals'] > 0) {
            $this->warn('--- Residuals ---');
            foreach ($payload['residuals'] as $r) {
                $this->line(" - [{$r['type']}] {$r['path']} :: {$r['message']}");
            }
        }
        if ($payload['counts']['neutralized_tests'] > 0) {
            $this->warn('--- Neutralized Tests ---');
            foreach ($payload['neutralized_tests'] as $t) {
                $this->line(" - {$t['path']} (comment_ratio={$t['comment_ratio']} lines={$t['lines']})");
            }
        }
        if ($payload['counts']['residuals'] === 0 && $payload['counts']['neutralized_tests'] === 0) {
            $this->info('No residual legacy artifacts or neutralized tests detected.');
        }
    }
}
