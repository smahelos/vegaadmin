<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Unified command to regenerate all DDD architecture baseline artifacts.
 * It wraps individual baseline commands to ensure consistent regeneration order
 * and provides optional copying into version-controlled architecture/baselines directory.
 */
class DddBaselinesRefreshCommand extends Command
{
    protected $signature = 'ddd:baselines:refresh
        {--write : Persist each baseline to storage (equivalent to passing --save to individual commands)}
        {--update-versioned : After regeneration, copy storage baselines into architecture/baselines (overwriting)}
        {--include-provider : Also regenerate provider snapshot (writes via providers:snapshot --write)}
        {--no-controller-thinness : Skip controller thinness baseline regeneration}
        {--no-frontend : Skip frontend eloquent baseline regeneration}
        {--no-api : Skip api eloquent baseline regeneration}
        {--dry-run : Do not write or copy any files (overrides --write / --update-versioned)}
        {--json-summary : Output JSON summary (for CI artifact)}
        {--include-legacy : Also run legacy report and include its counts in summary diff}';

    protected $description = 'Regenerate all architecture governance baselines (controller thinness, frontend/api Eloquent, provider snapshot).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $write = $dryRun ? false : $this->option('write');
        $updateVersioned = $dryRun ? false : $this->option('update-versioned');
        $includeProvider = $this->option('include-provider');
        $includeLegacy = $this->option('include-legacy');
        $jsonSummary = $this->option('json-summary');

        $previousCounts = $this->loadPreviousCounts();

        $errors = [];

        // Dynamic step counting
        $totalSteps = 0;
        if (!$this->option('no-controller-thinness')) { $totalSteps++; }
        if (!$this->option('no-frontend')) { $totalSteps++; }
        if (!$this->option('no-api')) { $totalSteps++; }
        if ($includeProvider) { $totalSteps++; }
        if ($includeLegacy) { $totalSteps++; }
        $step = 1;

        // Controller thinness baseline
        if (!$this->option('no-controller-thinness')) {
            $this->info('['.$step.'/'.$totalSteps.'] Regenerating controller thinness baseline');
            $step++;
            $code = $this->callSilently('ddd:controller-thinness:baseline', [ '--save' => $write ]);
            if ($code !== 0) { $errors[] = 'controller-thinness baseline failed (code '.$code.')'; }
        } else {
            $this->line('Skipping controller thinness baseline (flag --no-controller-thinness)');
        }

        // Frontend Eloquent baseline
        if (!$this->option('no-frontend')) {
            $this->info('['.$step.'/'.$totalSteps.'] Regenerating frontend controllers eloquent baseline');
            $step++;
            $code = $this->callSilently('ddd:frontend-eloquent:baseline', [ '--save' => $write ]);
            if ($code !== 0) { $errors[] = 'frontend eloquent baseline failed (code '.$code.')'; }
        } else {
            $this->line('Skipping frontend eloquent baseline (flag --no-frontend)');
        }

        // API Eloquent baseline
        if (!$this->option('no-api')) {
            $this->info('['.$step.'/'.$totalSteps.'] Regenerating API controllers eloquent baseline');
            $step++;
            $code = $this->callSilently('ddd:api-eloquent:baseline', [ '--save' => $write ]);
            if ($code !== 0) { $errors[] = 'api eloquent baseline failed (code '.$code.')'; }
        } else {
            $this->line('Skipping api eloquent baseline (flag --no-api)');
        }

        // Provider snapshot (optional)
        if ($includeProvider) {
            $this->info('['.$step.'/'.$totalSteps.'] Regenerating provider snapshot');
            $step++;
            // Respect dry-run: do not write when dry-run mode is active
            $code = $this->callSilently('ddd:providers:snapshot', [ '--write' => !$dryRun, '--format' => 'json' ]);
            if ($code !== 0) { $errors[] = 'provider snapshot failed (code '.$code.')'; }
            if ($dryRun) {
                $this->line('Dry-run: provider snapshot generation executed without writing file.');
            }
        } else {
            $this->line('Skipping provider snapshot (no --include-provider flag)');
        }

        // Legacy report (optional)
        if ($includeLegacy) {
            $this->info('['.$step.'/'.$totalSteps.'] Running legacy report');
            $step++;
            $legacyParams = [ '--json' => true ];
            if (!$dryRun) { $legacyParams['--write-baseline'] = true; }
            $code = $this->callSilently('ddd:legacy-report', $legacyParams);
            if ($code !== 0 && $code !== 2) { // code 2 = strict failure (not used here), treat >2 as error
                $errors[] = 'legacy report failed (code '.$code.')';
            }
            if ($dryRun) { $this->line('Dry-run: legacy report executed without writing baseline.'); }
        } else {
            $this->line('Skipping legacy report (no --include-legacy flag)');
        }

        // Copy into version-controlled baselines directory when requested
        if ($updateVersioned) {
            $this->info('Updating version-controlled baseline JSON files (architecture/baselines)');
            $vcDir = base_path('architecture/baselines');
            if (!is_dir($vcDir)) { @mkdir($vcDir, 0777, true); }
            $map = [
                'controller_thinness_baseline.json' => storage_path('app/metrics/controller_thinness_baseline.json'),
                'frontend_controller_eloquent_baseline.json' => storage_path('app/metrics/frontend_controller_eloquent_baseline.json'),
                'api_controller_eloquent_baseline.json' => storage_path('app/metrics/api_controller_eloquent_baseline.json'),
            ];
            foreach ($map as $target => $src) {
                if (!file_exists($src)) { $this->warn("Source missing (skip copy): $src"); continue; }
                copy($src, $vcDir.'/'.$target);
                $this->line("Updated $target");
            }
            if ($includeProvider) {
                // provider snapshot already written to architecture/baselines via its configured path
                $this->line('Provider snapshot already updated by providers:snapshot --write');
            }
        }
        if ($dryRun) {
            $this->info('Dry-run mode: no baseline files were written or copied.');
        }
        $diff = $this->computeDiff($previousCounts);

        if ($errors) {
            foreach ($errors as $e) { $this->error($e); }
            if ($jsonSummary) { $this->outputJsonSummary($diff, $errors, $dryRun); }
            return 1;
        }

        $this->info('All requested baselines regenerated successfully.');
        $this->renderDiffHuman($diff);
        if ($jsonSummary) { $this->outputJsonSummary($diff, $errors, $dryRun); }
        return 0;
    }

    private function loadPreviousCounts(): array
    {
        $dir = base_path('architecture/baselines');
        $result = [];
        $map = [
            'controller_thinness' => 'controller_thinness_baseline.json',
            'frontend' => 'frontend_controller_eloquent_baseline.json',
            'api' => 'api_controller_eloquent_baseline.json',
            'legacy' => 'legacy_report_latest.json',
        ];
        foreach ($map as $key => $file) {
            $path = $dir.'/'.$file;
            if (!is_file($path)) { continue; }
            $json = json_decode(file_get_contents($path), true);
            if (!$json) { continue; }
            if ($key === 'controller_thinness') {
                $result[$key] = $json['summary']['total_controllers'] ?? null;
            } else {
                if (in_array($key, ['frontend','api'])) {
                    $result[$key] = isset($json['controllers']) && is_array($json['controllers']) ? count($json['controllers']) : null;
                } elseif ($key === 'legacy') {
                    $result['legacy_residuals'] = $json['counts']['residuals'] ?? null;
                    $result['legacy_neutralized_tests'] = $json['counts']['neutralized_tests'] ?? null;
                }
            }
        }
        return $result;
    }

    private function computeDiff(array $previous): array
    {
        $current = [
            'controller_thinness' => $this->currentControllerThinnessCount(),
            'frontend' => $this->currentBaselineCount(storage_path('app/metrics/frontend_controller_eloquent_baseline.json')),
            'api' => $this->currentBaselineCount(storage_path('app/metrics/api_controller_eloquent_baseline.json')),
            'legacy_residuals' => $this->currentLegacyCount('residuals'),
            'legacy_neutralized_tests' => $this->currentLegacyCount('neutralized_tests'),
        ];
        $diff = [];
        foreach ($current as $key => $curr) {
            $prev = $previous[$key] ?? null;
            $diff[$key] = [
                'previous' => $prev,
                'current' => $curr,
                'delta' => ($prev !== null && $curr !== null) ? $curr - $prev : null,
            ];
        }
        return $diff;
    }

    private function currentControllerThinnessCount(): ?int
    {
        $path = storage_path('app/metrics/controller_thinness_baseline.json');
        if (!is_file($path)) return null;
        $json = json_decode(file_get_contents($path), true);
        return $json['summary']['total_controllers'] ?? null;
    }

    private function currentBaselineCount(string $path): ?int
    {
        if (!is_file($path)) return null;
        $json = json_decode(file_get_contents($path), true);
        if (!$json) return null;
        return isset($json['controllers']) && is_array($json['controllers']) ? count($json['controllers']) : null;
    }

    private function currentLegacyCount(string $type): ?int
    {
        $path = base_path('architecture/baselines/legacy_report_latest.json');
        if (!is_file($path)) return null;
        $json = json_decode(file_get_contents($path), true);
        if (!$json || !isset($json['counts'])) return null;
        return $type === 'residuals' ? ($json['counts']['residuals'] ?? null) : ($json['counts']['neutralized_tests'] ?? null);
    }

    private function renderDiffHuman(array $diff): void
    {
    $this->line('Baseline counts diff:');
        foreach ($diff as $k => $d) {
            $this->line(sprintf(' - %s: prev=%s current=%s delta=%s', $k, $d['previous'] ?? 'n/a', $d['current'] ?? 'n/a', $d['delta'] ?? 'n/a'));
        }
    }

    private function outputJsonSummary(array $diff, array $errors, bool $dryRun): void
    {
        $payload = [
            'status' => empty($errors) ? 'ok' : 'failed',
            'dry_run' => $dryRun,
            'diff' => $diff,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->line(json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }
}
