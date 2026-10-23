<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class ProvidersSnapshotCommand extends Command
{
    protected $signature = 'ddd:providers:snapshot {--write : Persist current snapshot} {--diff : Compare with existing snapshot} {--format=json : Output format json|human} {--strict : Treat warnings as errors}';

    protected $description = 'Generate or diff a snapshot of app service provider bindings (contract -> implementation -> provider)';

    public function handle(): int
    {
        $config = config('ddd_guard.provider_snapshot');
        if (! $config) {
            $this->error('Missing provider_snapshot config');

            return 1;
        }
        $snapshotPath = $config['path'];
        $allowedMoves = $config['allowed_moves'] ?? [];
        $strictMoves = (bool) ($config['strict_moves'] ?? false);
        $format = $this->option('format') === 'human' ? 'human' : 'json';

        $current = $this->buildCurrentSnapshot();

        $write = $this->option('write');
        $diff = $this->option('diff');
        $strictFlag = $this->option('strict');
        $errors = [];
        $warnings = [];

        if ($diff && is_file($snapshotPath)) {
            $previous = json_decode(file_get_contents($snapshotPath), true) ?: [];
            [$errors, $warnings] = $this->diffSnapshots(
                $previous,
                $current,
                $allowedMoves,
                $strictMoves || $strictFlag,
            );
        } elseif ($diff && ! is_file($snapshotPath)) {
            $warnings[] = 'No existing snapshot to diff against (first run).';
        }

        if ($write) {
            // Ensure directory
            @mkdir(
                dirname($snapshotPath),
                0777,
                true,
            );
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
            if (! empty($errors)) {
                $this->error('Errors:');
                foreach ($errors as $e) {
                    $this->line(' - ' . $e);
                }
            }
            if (! empty($warnings)) {
                $this->warn('Warnings:');
                foreach ($warnings as $w) {
                    $this->line(' - ' . $w);
                }
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
        $bindings = []; // contract => provider => [implementation, provider, method]

        foreach ($this->applicationProviderFiles() as $providerClass => $path) {
            $contents = $this->stripComments(file_get_contents($path));
            $imports = $this->extractImports($contents, $providerClass);
            $binds = $this->extractBinds(
                $contents,
                'bind',
                $imports,
            );
            $singletons = $this->extractBinds(
                $contents,
                'singleton',
                $imports,
            );
            if (empty($binds) && empty($singletons)) {
                continue;
            }

            $providers[$providerClass] = [
                'bind' => $binds,
                'singleton' => $singletons,
            ];
            // Map contracts; repeated bindings inside one provider (e.g. config-driven if/else) count as one
            foreach (['bind' => $binds, 'singleton' => $singletons] as $method => $pairs) {
                foreach ($pairs as $pair) {
                    $contract = $pair['abstract'];
                    if (isset($bindings[$contract][$providerClass])) {
                        continue;
                    }
                    $bindings[$contract][$providerClass] = [
                        'implementation' => $pair['concrete'],
                        'provider' => $providerClass,
                        'method' => $method,
                    ];
                }
            }
        }
        $bindings = array_map('array_values', $bindings);

        // Contract files (expected baseline)
        $contracts = [];
        $contractFiles = (new Finder)
            ->files()
            ->in([app_path('Domain'), app_path('Application')])
            ->name('*Interface.php')
            ->sortByName();
        foreach ($contractFiles as $cf) {
            $cfPath = $cf->getRealPath();
            $rel = str_replace(
                app_path() . '/',
                '',
                $cfPath,
            );
            $fqcn = 'App\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $rel,
            );
            $contracts[$fqcn] = $this->classifyContract($fqcn, $bindings);
        }

        return [
            'generated_at' => gmdate('c'),
            'providers' => $providers,
            'contracts' => $contracts,
        ];
    }

    /**
     * App-owned providers registered in bootstrap/providers.php, keyed by FQCN.
     */
    private function applicationProviderFiles(): array
    {
        $files = [];
        foreach (require base_path('bootstrap/providers.php') as $providerClass) {
            if (! str_starts_with($providerClass, 'App\\') || ! class_exists($providerClass)) {
                continue;
            }
            $files[$providerClass] = (new \ReflectionClass($providerClass))->getFileName();
        }

        return $files;
    }

    private function stripComments(string $contents): string
    {
        $code = '';
        foreach (token_get_all($contents) as $token) {
            if (is_array($token) && in_array(
                $token[0],
                [T_COMMENT, T_DOC_COMMENT],
                true,
            )) {
                continue;
            }
            $code .= is_array($token) ? $token[1] : $token;
        }

        return $code;
    }

    /**
     * Map of short class name (or alias) => FQCN from top-level `use` statements.
     * Key '' holds the provider's own namespace for resolving unqualified names.
     */
    private function extractImports(string $contents, string $providerClass): array
    {
        $imports = ['' => substr(
            $providerClass,
            0,
            strrpos($providerClass, '\\'),
        )];
        preg_match_all(
            '/^use\s+([\w\\\\]+)(?:\s+as\s+(\w+))?\s*;/m',
            $contents,
            $matches,
            PREG_SET_ORDER,
        );
        foreach ($matches as $m) {
            $fqcn = ltrim($m[1], '\\');
            $imports[$m[2] ?? substr(strrchr('\\' . $fqcn, '\\'), 1)] = $fqcn;
        }

        return $imports;
    }

    /**
     * Extract `$this->app->{method}(Abstract::class, Concrete::class|closure)` registrations.
     * String keys (e.g. 'payment.gateway.gopay') are skipped: they are not contracts.
     */
    private function extractBinds(
        string $contents,
        string $method,
        array $imports,
    ): array {
        $pattern = '/\$this->app->' . $method . '\(\s*([\w\\\\]+)::class\s*(?:,\s*(?:([\w\\\\]+)::class)?)?/';
        if (! preg_match_all(
            $pattern,
            $contents,
            $matches,
            PREG_SET_ORDER,
        )) {
            return [];
        }
        $result = [];
        foreach ($matches as $m) {
            $abstract = $this->resolveClassName($m[1], $imports);
            $concrete = match (true) {
                ! empty($m[2]) => $this->resolveClassName($m[2], $imports),
                str_ends_with(rtrim($m[0]), ',') => 'closure',
                default => $abstract,
            };
            $result[] = [
                'abstract' => $abstract,
                'concrete' => $concrete,
            ];
        }

        return $result;
    }

    private function resolveClassName(string $name, array $imports): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        $first = explode('\\', $name)[0];
        if (isset($imports[$first])) {
            return $imports[$first] . substr($name, strlen($first));
        }

        return str_contains($name, '\\') ? $name : $imports[''] . '\\' . $name;
    }

    private function classifyContract(string $fqcn, array $bindings): array
    {
        $entries = $bindings[$fqcn] ?? [];
        if (empty($entries)) {
            return [
                'status' => 'missing_binding',
                'actual' => [],
            ];
        }
        if (count($entries) > 1) {
            return [
                'status' => 'duplicate_bindings',
                'actual' => $entries,
            ];
        }

        return [
            'status' => 'ok',
            'actual' => $entries[0],
        ];
    }

    private function diffSnapshots(
        array $previous,
        array $current,
        array $allowedMoves,
        bool $strictMoves,
    ): array {
        $errors = [];
        $warnings = [];
        $prevContracts = $previous['contracts'] ?? [];
        $currContracts = $current['contracts'] ?? [];

        // Index allowed moves for quick lookup
        $movesIndex = [];
        foreach ($allowedMoves as $move) {
            $key = ($move['contract'] ?? '') . '|' . ($move['from'] ?? '') . '|' . ($move['to'] ?? '');
            $movesIndex[$key] = true;
        }

        // Detect new contracts without binding
        foreach ($currContracts as $contract => $meta) {
            if (! isset($prevContracts[$contract]) && $meta['status'] === 'missing_binding') {
                $errors[] = "New contract {$contract} without binding";
            }
        }
        // Detect provider changes
        foreach ($currContracts as $contract => $meta) {
            if (! isset($prevContracts[$contract])) {
                continue;
            }
            $prevMeta = $prevContracts[$contract];
            if (($prevMeta['status'] === 'ok' || $prevMeta['status'] === 'wrong_provider') && ($meta['status'] === 'ok' || $meta['status'] === 'wrong_provider')) {
                $prevProv = $prevMeta['actual']['provider'] ?? '';
                $currProv = $meta['actual']['provider'] ?? '';
                if ($prevProv && $currProv && $prevProv !== $currProv) {
                    $moveKey = $contract . '|' . $prevProv . '|' . $currProv;
                    if (isset($movesIndex[$moveKey])) {
                        $warnings[] = "Provider move allowed {$contract}: {$prevProv} -> {$currProv}";
                    } else {
                        $msg = "Provider changed {$contract}: {$prevProv} -> {$currProv}";
                        if ($strictMoves) {
                            $errors[] = $msg;
                        } else {
                            $warnings[] = $msg;
                        }
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
