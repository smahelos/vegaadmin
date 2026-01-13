<?php

namespace Tests\Feature\Architecture;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Enforces controller thinness does not regress beyond baseline tolerance.
 * Tolerance rules:
 *  - LOC per controller may grow by at most 15% or 30 LOC (whichever is smaller) versus baseline.
 *  - eloquent_static_calls must NOT increase (baseline upper bound).
 *  - db_facade_calls must NOT increase.
 *  - new_model_insts may increase by at most 2 absolute (allow small refactors) but should be avoided.
 */
class ControllerThinnessEnforcementTest extends TestCase
{
    private array $baseline;

    protected function setUp(): void
    {
        parent::setUp();
        $path = storage_path('app/metrics/controller_thinness_baseline.json');
        if (!file_exists($path)) {
            // Fallback to committed docs baseline (first-time CI run or cleared storage)
            $docsBaseline = base_path('architecture/baselines/controller_thinness_baseline.json');
            $this->assertFileExists($docsBaseline, 'Committed controller thinness baseline missing (architecture/baselines/controller_thinness_baseline.json). Generate with: docker exec INVOICE-php-fpm php artisan ddd:controller-thinness:baseline --save then copy to architecture/baselines and commit.');
            // Ensure target directory exists
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            copy($docsBaseline, $path);
        }
        $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->baseline = $json['controllers'];
    }

    #[Test]
    public function controllers_do_not_exceed_baseline_tolerances(): void
    {
        // Re-run analyzer logic inline (duplicate minimal logic for determinism)
        $current = $this->analyzeControllers();

        $violations = [];
        foreach ($current as $controller => $metrics) {
            if (!isset($this->baseline[$controller])) {
                // New controller: allow but ensure starting point has no direct DB usage beyond acceptable
                if ($metrics['eloquent_static_calls'] > 0 || $metrics['db_facade_calls'] > 0) {
                    $violations[] = "$controller: new controller introduces direct DB usage (eloquent_static_calls={$metrics['eloquent_static_calls']}, db_facade_calls={$metrics['db_facade_calls']})";
                }
                continue;
            }
            $base = $this->baseline[$controller];

            // LOC tolerance
            $locLimit = min((int)ceil($base['loc'] * 1.15), $base['loc'] + 30);
            if ($metrics['loc'] > $locLimit) {
                $violations[] = "$controller: LOC {$metrics['loc']} exceeds limit $locLimit (baseline {$base['loc']})";
            }
            // Eloquent static calls must not increase
            if ($metrics['eloquent_static_calls'] > $base['eloquent_static_calls']) {
                $violations[] = "$controller: eloquent_static_calls {$metrics['eloquent_static_calls']} > baseline {$base['eloquent_static_calls']}";
            }
            // DB facade calls must not increase
            if ($metrics['db_facade_calls'] > $base['db_facade_calls']) {
                $violations[] = "$controller: db_facade_calls {$metrics['db_facade_calls']} > baseline {$base['db_facade_calls']}";
            }
            // new model instantiations small tolerance (+2)
            if ($metrics['new_model_insts'] > $base['new_model_insts'] + 2) {
                $violations[] = "$controller: new_model_insts {$metrics['new_model_insts']} exceeds baseline {$base['new_model_insts']} +2 allowance";
            }
        }

        if (!empty($violations)) {
            $this->fail("Controller thinness regressions detected:\n".implode("\n", $violations));
        }
        $this->assertTrue(true); // Passed
    }

    private function analyzeControllers(): array
    {
        $dir = app_path('Http/Controllers');
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            if ($file->getExtension() !== 'php') continue;
            $files[] = $file->getPathname();
        }
        $result = [];
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $rel = str_replace(app_path().'/', '', $file);
            $result[$rel] = $this->analyzeContent($content);
        }
        return $result;
    }

    private function analyzeContent(string $content): array
    {
        $lines = array_values(array_filter(array_map('rtrim', explode("\n", $content)), fn($l)=>$l !== ''));
        $loc = count($lines);
        $eloquentStatic = 0;
        $methods = ['where','create','find','all','first','update','delete','pluck','paginate','orderBy','count','sum','avg','min','max'];
        foreach ($methods as $m) {
            if (preg_match_all('/App\\\\Models\\\\[A-Za-z0-9_]+::'.$m.'\s*\(/', $content, $mm)) {
                $eloquentStatic += count($mm[0]);
            }
        }
        $newModel = preg_match_all('/new\s+App\\\\Models\\\\[A-Za-z0-9_]+\s*\(/', $content, $mm) ? count($mm[0]) : 0;
        $dbFacade = preg_match_all('/DB::[A-Za-z_]+\s*\(/', $content, $mm) ? count($mm[0]) : 0;
        $publicMethods = preg_match_all('/public\s+function\s+(?!__construct)([A-Za-z0-9_]+)\s*\(/', $content, $mm) ? count($mm[0]) : 0;
        return [
            'loc' => $loc,
            'eloquent_static_calls' => $eloquentStatic,
            'new_model_insts' => $newModel,
            'db_facade_calls' => $dbFacade,
            'public_method_count' => $publicMethods,
        ];
    }
}
