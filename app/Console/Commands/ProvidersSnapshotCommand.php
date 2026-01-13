<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class ProvidersSnapshotCommand extends Command
{
    protected $signature = 'ddd:providers:snapshot {--write : Persist current snapshot} {--diff : Compare with existing snapshot} {--format=json : Output format json|human} {--strict : Treat warnings as errors}';
    protected $description = 'Generate or diff a snapshot of domain service provider bindings (contract -> implementation -> provider)';

    public function handle(): int
    {
        $config = config('ddd_guard.provider_snapshot');
        if (!$config) {
            $this->error('Missing provider_snapshot config');
            return 1;
        }
        $snapshotPath = $config['path'];
        $allowedMoves = $config['allowed_moves'] ?? [];
        $strictMoves = (bool)($config['strict_moves'] ?? false);
        $format = $this->option('format') === 'human' ? 'human' : 'json';

        $current = $this->buildCurrentSnapshot();

        $write = $this->option('write');
        $diff = $this->option('diff');
        $strictFlag = $this->option('strict');
        $errors = [];
        $warnings = [];

        if ($diff && is_file($snapshotPath)) {
            $previous = json_decode(file_get_contents($snapshotPath), true) ?: [];
            [$errors, $warnings] = $this->diffSnapshots($previous, $current, $allowedMoves, $strictMoves || $strictFlag);
        } elseif ($diff && !is_file($snapshotPath)) {
            $warnings[] = 'No existing snapshot to diff against (first run).';
        }

        if ($write) {
            // Ensure directory
            @mkdir(dirname($snapshotPath), 0777, true);
            file_put_contents($snapshotPath, json_encode($current, JSON_PRETTY_PRINT));
        }

        if ($format === 'json') {
            $payload = [
                'status' => empty($errors) ? 'ok' : 'failed',
                'errors' => $errors,
                'warnings' => $warnings,
                'snapshot' => $current,
            ];
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
        } else {
            if (!empty($errors)) {
                $this->error('Errors:');
                foreach ($errors as $e) { $this->line(' - '.$e); }
            }
            if (!empty($warnings)) {
                $this->warn('Warnings:');
                foreach ($warnings as $w) { $this->line(' - '.$w); }
            }
            if (empty($errors)) {
                $this->info('Snapshot status OK');
            }
        }

        return empty($errors) ? 0 : 1;
    }

    private function buildCurrentSnapshot(): array
    {
        $providers = [];
        $bindings = []; // contract => [impl, provider]
        $providerFiles = (new Finder())
            ->files()
            ->in(app_path('Providers'))
            ->name('*DomainServiceProvider.php');

        foreach ($providerFiles as $file) {
            $contents = $file->getContents();
            $providerClass = $this->fqcnFromFile($contents) ?? $file->getFilenameWithoutExtension();
            $binds = $this->extractBinds($contents, 'bind');
            $singletons = $this->extractBinds($contents, 'singleton');

            $providers[$providerClass] = [
                'bind' => $binds,
                'singleton' => $singletons,
            ];
            // Map contracts
            foreach (['bind' => $binds, 'singleton' => $singletons] as $method => $pairs) {
                foreach ($pairs as $pair) {
                    $contract = $pair['abstract'];
                    $impl = $pair['concrete'];
                    if (!isset($bindings[$contract])) {
                        $bindings[$contract] = [];
                    }
                    $bindings[$contract][] = [
                        'implementation' => $impl,
                        'provider' => $providerClass,
                        'method' => $method,
                    ];
                }
            }
        }

        // Contract files (expected baseline)
        $contracts = [];
        $contractFiles = (new Finder())
            ->files()
            ->in(app_path('Domain'))
            ->name('*Interface.php');
        foreach ($contractFiles as $cf) {
            $cfPath = $cf->getRealPath();
            $rel = str_replace(app_path().'/', '', $cfPath);
            $fqcn = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $rel);
            $contracts[$fqcn] = $this->classifyContract($fqcn, $bindings);
        }

        return [
            'generated_at' => gmdate('c'),
            'providers' => $providers,
            'contracts' => $contracts,
        ];
    }

    private function extractBinds(string $contents, string $method): array
    {
        $pattern = '/\\$this->app->'.$method.'\(\s*([^,]+?)\s*,\s*([^\)]+?)\s*\)\s*;/';
        if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            return [];
        }
        $result = [];
        foreach ($matches as $m) {
            $abstract = $this->normalizeArgument($m[1]);
            $concrete = $this->normalizeArgument($m[2]);
            $result[] = [
                'abstract' => $abstract,
                'concrete' => $concrete,
            ];
        }
        return $result;
    }

    private function normalizeArgument(string $raw): string
    {
        $raw = trim($raw);
        // Remove wrapping quotes
        $raw = trim($raw, "'\"");
        // Remove trailing inline comments if any
        if (str_contains($raw, '//')) {
            $raw = preg_replace('/\/\/.*$/', '', $raw);
            $raw = trim($raw);
        }
        // If it's an array or closure we just return raw (not a simple FQCN binding)
        if (str_starts_with($raw, '[') || str_starts_with($raw, 'function') || str_starts_with($raw, 'fn(')) {
            return $raw;
        }
        // Strip trailing ::class to get the FQCN string
        if (preg_match('/::class$/', $raw)) {
            $raw = preg_replace('/::class$/', '', $raw);
        }
        // Remove leading backslashes
        $raw = ltrim($raw, '\\');
        // Normalize whitespace
        $raw = trim($raw);
        return $raw;
    }

    private function fqcnFromFile(string $contents): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $contents, $ns) && preg_match('/class\s+(\w+)/', $contents, $cl)) {
            return trim($ns[1]).'\\'.trim($cl[1]);
        }
        return null;
    }

    private function classifyContract(string $fqcn, array $bindings): array
    {
        $expectedProvider = $this->expectedProviderForContract($fqcn);
        $entries = $bindings[$fqcn] ?? [];
        if (empty($entries)) {
            return [
                'status' => 'missing_binding',
                'expected_provider' => $expectedProvider,
                'actual' => [],
            ];
        }
        if (count($entries) > 1) {
            return [
                'status' => 'duplicate_bindings',
                'expected_provider' => $expectedProvider,
                'actual' => $entries,
            ];
        }
        $entry = $entries[0];
        $status = ($entry['provider'] === $expectedProvider) ? 'ok' : 'wrong_provider';
        return [
            'status' => $status,
            'expected_provider' => $expectedProvider,
            'actual' => $entry,
        ];
    }

    private function expectedProviderForContract(string $fqcn): string
    {
        // Pattern: App\Domain\<Domain>\Contracts\FooInterface
        if (!str_starts_with($fqcn, 'App\\Domain\\')) {
            return '';
        }
        $parts = explode('\\', substr($fqcn, strlen('App\\Domain\\')));
        if (count($parts) < 2) { return ''; }
        $domain = $parts[0];
        return 'App\\Providers\\'.$domain.'DomainServiceProvider';
    }

    private function diffSnapshots(array $previous, array $current, array $allowedMoves, bool $strictMoves): array
    {
        $errors = [];
        $warnings = [];
        $prevContracts = $previous['contracts'] ?? [];
        $currContracts = $current['contracts'] ?? [];

        // Index allowed moves for quick lookup
        $movesIndex = [];
        foreach ($allowedMoves as $move) {
            $key = ($move['contract'] ?? '').'|'.($move['from'] ?? '').'|'.($move['to'] ?? '');
            $movesIndex[$key] = true;
        }

        // Detect new contracts without binding
        foreach ($currContracts as $contract => $meta) {
            if (!isset($prevContracts[$contract]) && $meta['status'] === 'missing_binding') {
                $errors[] = "New contract {$contract} without binding";
            }
        }
        // Detect provider changes
        foreach ($currContracts as $contract => $meta) {
            if (!isset($prevContracts[$contract])) { continue; }
            $prevMeta = $prevContracts[$contract];
            if (($prevMeta['status'] === 'ok' || $prevMeta['status'] === 'wrong_provider') && ($meta['status'] === 'ok' || $meta['status'] === 'wrong_provider')) {
                $prevProv = $prevMeta['actual']['provider'] ?? '';
                $currProv = $meta['actual']['provider'] ?? '';
                if ($prevProv && $currProv && $prevProv !== $currProv) {
                    $moveKey = $contract.'|'.$prevProv.'|'.$currProv;
                    if (isset($movesIndex[$moveKey])) {
                        $warnings[] = "Provider move allowed {$contract}: {$prevProv} -> {$currProv}";
                    } else {
                        $msg = "Provider changed {$contract}: {$prevProv} -> {$currProv}";
                        if ($strictMoves) { $errors[] = $msg; } else { $warnings[] = $msg; }
                    }
                }
            }
        }
        // Duplicates
        foreach ($currContracts as $contract => $meta) {
            if ($meta['status'] === 'duplicate_bindings') {
                $errors[] = "Duplicate bindings for {$contract}";
            }
        }
        return [$errors, $warnings];
    }
}
