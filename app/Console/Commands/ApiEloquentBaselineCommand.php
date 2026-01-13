<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates baseline JSON for API controller direct Eloquent static call usage.
 * Mirrors the frontend baseline command for symmetry and CI drift detection.
 */
class ApiEloquentBaselineCommand extends Command
{
    protected $signature = 'ddd:api-eloquent:baseline {--save : Persist baseline JSON} {--path= : Optional custom output path}';
    protected $description = 'Generate baseline list of API controllers + rule metadata for direct Eloquent static call guard.';

    private array $eloquentStaticMethods = ['where','create','find','all','first','update','delete','pluck','paginate','orderBy','count','sum','avg','min','max'];

    public function handle(): int
    {
        $controllersDir = app_path('Http/Controllers/Api');
        if (!is_dir($controllersDir)) {
            $this->error('API controllers directory not found: '.$controllersDir);
            return self::FAILURE;
        }

        $files = $this->gatherPhpFiles($controllersDir);
        sort($files);
        $rel = array_map(fn($f)=> Str::after($f, app_path().'/'), $files);

        $baseline = [
            'rule' => [
                'description' => 'Each API controller must have at most 1 direct Eloquent static call.',
                'max_eloquent_static_calls' => 1,
                'justification' => 'API thin controller guideline'
            ],
            'controllers' => $rel,
            'generated_at' => now()->toIso8601String(),
        ];

        if ($this->option('save')) {
            $outDir = $this->option('path') ? rtrim($this->option('path'), '/') : storage_path('app/metrics');
            if (!is_dir($outDir)) {
                mkdir($outDir, 0777, true);
            }
            $file = $outDir.'/api_controller_eloquent_baseline.json';
            file_put_contents($file, json_encode($baseline, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            $this->info('Baseline saved to '.$file);
        }

        $this->table(['Controller','DirectEloquentStaticCalls'], collect($files)->map(function($f){
            $content = file_get_contents($f);
            return [Str::after($f, app_path().'/'), $this->countEloquentStaticCalls($content)];
        })->toArray());

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

    private function countEloquentStaticCalls(string $content): int
    {
        $total = 0;
        foreach ($this->eloquentStaticMethods as $m) {
            if (preg_match_all('/App\\\\Models\\\\[A-Za-z0-9_]+::'.$m.'\s*\(/', $content, $mm)) {
                $total += count($mm[0]);
            }
        }
        if (preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z0-9_]+);/m', $content, $imports)) {
            $imported = array_unique($imports[1]);
            foreach ($imported as $short) {
                foreach ($this->eloquentStaticMethods as $m) {
                    if (preg_match_all('/(?<![A-Za-z0-9_])'.$short.'::'.$m.'\s*\(/', $content, $mm)) {
                        $total += count($mm[0]);
                    }
                }
            }
        }
        return $total;
    }
}
