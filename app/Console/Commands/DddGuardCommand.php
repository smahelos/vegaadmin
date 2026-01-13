<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * DDD Guard command enforces migration constraints (legacy freeze + pattern bans).
 */
class DddGuardCommand extends Command
{
    /** @var string */
    protected $signature = 'ddd:guard {--format=human : Output format human|json} {--di : Enable experimental DI concrete service sniff} {--fail-on-advisory : Fail also when only advisories are present} {--suppress-exit : Force exit code 0 (used in selected tests)}';
    /** @var string */
    protected $description = 'Run DDD migration guard checks (legacy services freeze & forbidden patterns)';

    private Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $config = config('ddd_guard');
        if (!$config) {
            $this->error('Missing config/ddd_guard.php');
            return 1;
        }

    $violations = [];
    $advisories = [];
    $diReport = [ 'violations' => [], 'advisories' => [] ];

        // 1. Frozen legacy directories (no new files)
        foreach ($config['forbidden_paths'] as $rule) {
            $path = $rule['path'];
            $mode = $rule['mode'] ?? 'no_new_files';

            if ($mode === 'must_not_exist') {
                if (is_dir($path)) {
                    // Any presence is a violation; list first few entries for context
                    $sample = [];
                    try {
                        foreach ($this->files->allFiles($path) as $f) {
                            $sample[] = $f->getFilename();
                            if (count($sample) >= 5) { break; }
                        }
                    } catch (\Throwable $e) {
                        // ignore enumeration errors
                    }
                    $violations[] = [
                        'type' => 'forbidden_directory',
                        'path' => $path,
                        'message' => 'Directory must not exist (legacy root): '.basename($path).(empty($sample) ? '' : ' (sample: '.implode(', ', $sample).')'),
                    ];
                }
                continue; // skip standard handling
            }

            if (!is_dir($path)) {
                continue; // Already removed -> no issue
            }
            $allowedFiles = $rule['allowed_files'] ?? [];
            $allowedDirs = $rule['allowed_directories'] ?? [];

            if ($mode === 'no_new_files') {
                foreach ($this->files->files($path) as $file) {
                    $base = $file->getFilename();
                    if (!in_array($base, $allowedFiles, true)) {
                        $violations[] = [
                            'type' => 'new_file',
                            'path' => $file->getPathname(),
                            'message' => "Legacy directory freeze violation: {$base}",
                        ];
                    }
                }
                foreach ($this->files->directories($path) as $dir) {
                    $dirBase = basename($dir);
                    if (!in_array($dirBase, $allowedDirs, true)) {
                        $violations[] = [
                            'type' => 'new_directory',
                            'path' => $dir,
                            'message' => "Legacy directory freeze violation (directory): {$dirBase}",
                        ];
                    }
                }
            }
        }

        // 2. Forbidden patterns in codebase (supports optional path scoping per rule)
        $patterns = $config['forbidden_patterns'] ?? [];
        if ($patterns) {
            foreach ($patterns as $patternRule) {
                $pattern = $patternRule['pattern'] ?? null;
                if (!$pattern) { continue; }
                $description = $patternRule['description'] ?? 'Forbidden pattern';
                $scopedPaths = $patternRule['paths'] ?? [base_path()];
                foreach ($scopedPaths as $scanPath) {
                    if (!is_dir($scanPath)) { continue; }
                    $finder = (new Finder())
                        ->files()
                        ->in($scanPath)
                        ->name('*.php');
                    foreach ($config['exclude_paths'] as $exclude) {
                        try {
                            $finder->exclude($exclude);
                        } catch (\Exception $e) {
                            // Skip exclude that causes problems (e.g., non-existent directories)
                        }
                    }
                    try {
                        foreach ($finder as $file) {
                        $contents = $file->getContents();
                        if (!preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                            continue;
                        }
                        $lines = preg_split('/\r?\n/', $contents);
                        $offsetsToLines = [];
                        $currentOffset = 0;
                        foreach ($lines as $index => $line) {
                            $length = strlen($line) + 1;
                            for ($i = $currentOffset; $i < $currentOffset + $length; $i++) {
                                $offsetsToLines[$i] = $index + 1;
                            }
                            $currentOffset += $length;
                        }
                        foreach ($matches[0] as $match) {
                            [$text, $offset] = $match;
                            $lineNumber = $offsetsToLines[$offset] ?? null;
                            $violations[] = [
                                'type' => 'pattern',
                                'path' => $file->getRealPath(),
                                'line' => $lineNumber,
                                'match' => $text,
                                'message' => $description,
                            ];
                        }
                    }
                    } catch (\Exception $e) {
                        // Skip scanning paths that cause problems (e.g., trying to iterate over non-existent directories)
                    }
                }
            }
        }

        // 3. Optional DI sniff (Phase 1 skeleton)
        if ($this->option('di') && ($config['di_enabled'] ?? false)) {
            $this->scanDiConcreteInjections($config, $violations, $advisories, $diReport);
        }

        $format = $this->option('format');

        $failOnAdvisory = $this->option('fail-on-advisory') || ($config['fail_on_advisory'] ?? false);

        if (empty($violations)) {
            if ($format === 'json') {
                $payload = [
                    'status' => 'ok',
                    'violations' => [],
                    'advisories' => $advisories,
                    'di' => $this->option('di') ? $diReport : null,
                ];
                $this->persistJsonIfConfigured($config, $payload);
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));
            } else {
                $this->info(__('ddd_guard.no_violations'));
                if (!empty($advisories)) {
                    $this->warn('Advisories (non-blocking): '.count($advisories));
                }
                if ($this->option('di') && (!empty($diReport['violations']) || !empty($diReport['advisories']))) {
                    $this->line('DI Scan: '.count($diReport['violations']).' violations, '.count($diReport['advisories']).' advisories');
                }
            }
            if ($this->option('suppress-exit')) { return 0; }
            if ($failOnAdvisory && !empty($advisories)) { return 1; }
            return 0;
        }

        if ($format === 'json') {
            $payload = [
                'status' => 'failed',
                'count' => count($violations),
                'violations' => $violations,
                'advisories' => $advisories,
                'di' => $this->option('di') ? $diReport : null,
            ];
            $this->persistJsonIfConfigured($config, $payload);
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
        } else {
            $this->error(__('ddd_guard.violations_found'));
            foreach ($violations as $v) {
                $lineInfo = isset($v['line']) ? (':'.$v['line']) : '';
                $this->line(" - [{$v['type']}] {$v['path']}{$lineInfo} :: {$v['message']}");
            }
            if (!empty($advisories)) {
                $this->warn('Advisories (non-blocking):');
                foreach ($advisories as $a) {
                    $lineInfo = isset($a['line']) ? (':'.$a['line']) : '';
                    $this->line(" - [advisory] {$a['path']}{$lineInfo} :: {$a['message']}");
                }
            }
            $this->error(str_replace(':count', (string)count($violations), __('ddd_guard.summary')));
        }
    if ($this->option('suppress-exit')) { return 0; }
    if (!empty($violations)) { return 1; }
    if ($failOnAdvisory && !empty($advisories)) { return 1; }
    return 0;
    }

    /**
     * Phase 1 skeleton DI scan: detect direct service injection/import.
     * Populates $violations (hard) and $advisories (non-blocking) arrays.
     */
    protected function scanDiConcreteInjections(array $config, array &$violations, array &$advisories, array &$diReport): void
    {
        $paths = $config['di_scan_paths'] ?? [];
        if (empty($paths)) {
            return;
        }
        $serviceSuffix = $config['di_service_suffix'] ?? 'Service';
        $interfaceSuffix = $config['di_interface_suffix'] ?? 'Interface';
        $allowlist = $config['di_allowlist'] ?? [];
        $missingInterfaceAdvisory = $config['di_missing_interface_advisory'] ?? true;

        $useRegex = '/use\s+(App\\\\Domain\\\\[A-Z][A-Za-z0-9]+\\\\Services\\\\([A-Z][A-Za-z0-9]+'.$serviceSuffix.'))\s*;/m';
        $ctorRegex = '/__construct\([^)]*?App\\\\Domain\\\\[A-Z][A-Za-z0-9]+\\\\Services\\\\[A-Z][A-Za-z0-9]+'.$serviceSuffix.'\s+\$/s';

        foreach ($paths as $scanPath) {
            if (!is_dir($scanPath)) {
                continue;
            }
            $phpFiles = (new Finder())->files()->in($scanPath)->name('*.php');
            foreach ($phpFiles as $file) {
                $contents = $file->getContents();
                // Use import detection
                if (preg_match_all($useRegex, $contents, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[1] as $idx => $m) {
                        [$fqcn, $offset] = $m;
                        if (in_array($fqcn, $allowlist, true)) {
                            continue;
                        }
                        $line = $this->offsetToLine($contents, $offset);
                        if ($this->classExistsByPathGuess($fqcn, $interfaceSuffix)) {
                            $violation = [
                                'type' => 'di_concrete_import',
                                'path' => $file->getRealPath(),
                                'line' => $line,
                                'message' => "Concrete service import {$fqcn} (use interface instead)",
                            ];
                            $violations[] = $violation;
                            $diReport['violations'][] = $violation;
                        } elseif ($missingInterfaceAdvisory) {
                            $advisory = [
                                'type' => 'di_missing_interface',
                                'path' => $file->getRealPath(),
                                'line' => $line,
                                    'message' => "Concrete service import {$fqcn} (interface missing – create matching *ServiceInterface in Contracts)",
                            ];
                            $advisories[] = $advisory;
                            $diReport['advisories'][] = $advisory;
                        }
                    }
                }
                // Constructor parameter detection
                if (preg_match_all($ctorRegex, $contents, $ctorMatches, PREG_OFFSET_CAPTURE)) {
                    foreach ($ctorMatches[0] as $m) {
                        [, $offset] = $m;
                        $line = $this->offsetToLine($contents, $offset);
                        // Simplistic extraction of service class within match
                        if (preg_match('/App\\\\Domain\\\\[A-Z][A-Za-z0-9]+\\\\Services\\\\([A-Z][A-Za-z0-9]+'.$serviceSuffix.')/', $m[0], $svcMatch)) {
                            $svcFqcn = $svcMatch[0];
                            if (in_array($svcFqcn, $allowlist, true)) {
                                continue;
                            }
                            if ($this->classExistsByPathGuess($svcFqcn, $interfaceSuffix)) {
                                $violation = [
                                    'type' => 'di_concrete_constructor',
                                    'path' => $file->getRealPath(),
                                    'line' => $line,
                                    'message' => "Concrete service ctor injection {$svcFqcn} (use interface)",
                                ];
                                $violations[] = $violation;
                                $diReport['violations'][] = $violation;
                            } elseif ($missingInterfaceAdvisory) {
                                $advisory = [
                                    'type' => 'di_missing_interface',
                                    'path' => $file->getRealPath(),
                                    'line' => $line,
                                    'message' => "Concrete service ctor injection {$svcFqcn} (interface missing)",
                                ];
                                $advisories[] = $advisory;
                                $diReport['advisories'][] = $advisory;
                            }
                        }
                    }
                }
            }
        }
    }

    /** Convert byte offset to 1-based line number */
    private function offsetToLine(string $contents, int $offset): int
    {
        $before = substr($contents, 0, $offset);
        return substr_count($before, "\n") + 1;
    }

    /**
     * Naive guess if interface exists: replace `/Services/FooService` with `/Contracts/FooServiceInterface.php`.
     */
    private function classExistsByPathGuess(string $serviceFqcn, string $interfaceSuffix): bool
    {
        if (!str_starts_with($serviceFqcn, 'App\\Domain\\')) {
            return false;
        }
        // Pattern: App\Domain\<Domain>\Services\<ServiceClass>
        $trimmed = substr($serviceFqcn, strlen('App\\Domain\\'));
        $segments = explode('\\', $trimmed);
        // Expect at least: [Domain, Services, FooService]
        if (count($segments) < 3) {
            return false;
        }
        if ($segments[1] !== 'Services') {
            return false;
        }
        $domain = $segments[0];
        $serviceClass = $segments[2];
        if (!$serviceClass || !str_ends_with($serviceClass, 'Service')) {
            return false; // Only enforce for *Service classes
        }
        $interfaceFile = base_path('app/Domain/'.$domain.'/Contracts/'.$serviceClass.$interfaceSuffix.'.php');
        return is_file($interfaceFile);
    }

    /** Persist JSON payload if json_report_path configured */
    private function persistJsonIfConfigured(array $config, array $payload): void
    {
        if (isset($config['json_report_path']) && is_string($config['json_report_path'])) {
            try {
                @file_put_contents($config['json_report_path'], json_encode($payload, JSON_PRETTY_PRINT));
            } catch (\Throwable $e) {
                // Silent fail – report persistence is best-effort
            }
        }
    }
}
