<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ControllerThinnessBaselineCommand extends Command
{
    protected $signature = 'ddd:controller-thinness:baseline {--save : Persist JSON baseline file}';
    protected $description = 'Generate baseline metrics for controller thinness (Eloquent usage vs service delegation).';

    private array $eloquentStaticMethods = [
        'where','create','find','all','first','update','delete','pluck','paginate','orderBy','count','sum','avg','min','max'
    ];

    public function handle(): int
    {
        $basePath = app_path('Http/Controllers');
        if (!is_dir($basePath)) {
            $this->error('Controllers path not found: '.$basePath);
            return self::FAILURE;
        }

        $files = $this->gatherPhpFiles($basePath);
        $metrics = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $rel = Str::after($file, app_path().'/');
            $metrics[$rel] = $this->analyzeController($content);
        }

        // Aggregate summary
        $summary = [
            'total_controllers' => count($metrics),
            'avg_loc' => $this->average(array_column($metrics, 'loc')),
            'avg_eloquent_static_calls' => $this->average(array_column($metrics, 'eloquent_static_calls')),
            'controllers_with_direct_db_calls' => count(array_filter($metrics, fn($m) => $m['db_facade_calls'] > 0)),
            'timestamp' => now()->toIso8601String(),
        ];

        $output = [
            'summary' => $summary,
            'controllers' => $metrics,
        ];

        if ($this->option('save')) {
            $path = storage_path('app/metrics');
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
            file_put_contents($path.'/controller_thinness_baseline.json', json_encode($output, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            $this->info('Baseline saved to storage/app/metrics/controller_thinness_baseline.json');
        }

        $this->table(
            ['Controller','LOC','EloquentStatic','newModel','DB','ConstructorDI','PublicMethods'],
            collect($metrics)->map(function($m, $k){
                return [
                    $k,
                    $m['loc'],
                    $m['eloquent_static_calls'],
                    $m['new_model_insts'],
                    $m['db_facade_calls'],
                    $m['constructor_domain_injections'],
                    $m['public_method_count'],
                ];
            })->sortByDesc(fn($row)=>$row[2])->values()->toArray()
        );

        $this->line('Summary: '.json_encode($summary, JSON_UNESCAPED_SLASHES));
        $this->line('TIP: Use this baseline to enforce max allowed direct Eloquent calls per controller.');
        return self::SUCCESS;
    }

    private function gatherPhpFiles(string $dir): array
    {
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            if ($file->getExtension() !== 'php') continue;
            $files[] = $file->getPathname();
        }
        return $files;
    }

    private function analyzeController(string $content): array
    {
        $lines = array_values(array_filter(array_map('rtrim', explode("\n", $content)), fn($l)=>$l !== ''));
        $loc = count($lines);

        // Count Eloquent static usage: App\Models\Whatever::method(
        $eloquentStaticCalls = 0;
        foreach ($this->eloquentStaticMethods as $m) {
            $pattern = '/App\\\\Models\\\\[A-Za-z0-9_]+::'.$m.'\s*\(/';
            if (preg_match_all($pattern, $content, $mm)) {
                $eloquentStaticCalls += count($mm[0]);
            }
        }

        // new Model instantiations
        $newModelInsts = 0;
        if (preg_match_all('/new\s+App\\\\Models\\\\[A-Za-z0-9_]+\s*\(/', $content, $mm)) {
            $newModelInsts = count($mm[0]);
        }

        // DB facade calls
        $dbFacadeCalls = 0;
        if (preg_match_all('/DB::[A-Za-z_]+\s*\(/', $content, $mm)) {
            $dbFacadeCalls = count($mm[0]);
        }

        // Constructor domain injections (Contracts\*Interface type-hints)
        $constructorDomainInjections = 0;
        if (preg_match('/function __construct\((.*?)\)\s*\{/', $content, $ctor)) {
            $params = $ctor[1];
            if (preg_match_all('/App\\\\Domain\\\\[A-Za-z0-9_\\\\]+\\\\Contracts\\\\[A-Za-z0-9_]+Interface/', $params, $pm)) {
                $constructorDomainInjections = count($pm[0]);
            }
        }

        // Public method count (excluding constructor)
        $publicMethodCount = 0;
        if (preg_match_all('/public\s+function\s+(?!__construct)([A-Za-z0-9_]+)\s*\(/', $content, $mm)) {
            $publicMethodCount = count($mm[0]);
        }

        return [
            'loc' => $loc,
            'eloquent_static_calls' => $eloquentStaticCalls,
            'new_model_insts' => $newModelInsts,
            'db_facade_calls' => $dbFacadeCalls,
            'constructor_domain_injections' => $constructorDomainInjections,
            'public_method_count' => $publicMethodCount,
        ];
    }

    private function average(array $values): float
    {
        $values = array_filter($values, fn($v)=>$v !== null);
        if (count($values) === 0) return 0.0;
        return round(array_sum($values) / count($values), 2);
    }
}
