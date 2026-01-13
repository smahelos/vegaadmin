<?php

namespace Tests\Feature\Architecture;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Ensures no API controller exceeds 1 direct Eloquent static call.
 * Baseline stored in storage/app/metrics/api_controller_eloquent_baseline.json
 */
class ApiControllersEloquentUsageTest extends TestCase
{
    private array $controllers;
    private int $maxAllowed;

    protected function setUp(): void
    {
        parent::setUp();
        $path = storage_path('app/metrics/api_controller_eloquent_baseline.json');
        $this->assertFileExists($path, 'Missing API controller baseline JSON: create api_controller_eloquent_baseline.json');
        $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->controllers = $json['controllers'];
        $this->maxAllowed = $json['rule']['max_eloquent_static_calls'] ?? 1;
    }

    #[Test]
    public function api_controllers_do_not_exceed_direct_eloquent_call_threshold(): void
    {
        $violations = [];
        foreach ($this->controllers as $relPath) {
            $full = app_path($relPath);
            if (!file_exists($full)) {
                continue; // new or removed
            }
            $content = file_get_contents($full);
            $count = $this->countEloquentStaticCalls($content);
            if ($count > $this->maxAllowed) {
                $violations[] = $relPath." has $count direct Eloquent static calls (limit {$this->maxAllowed})";
            }
        }
        if ($violations) {
            $this->fail("API controller thinness violations:\n".implode("\n", $violations));
        }
        $this->assertTrue(true);
    }

    private function countEloquentStaticCalls(string $content): int
    {
        $methods = ['where','create','find','all','first','update','delete','pluck','paginate','orderBy','count','sum','avg','min','max'];
        $total = 0;
        foreach ($methods as $m) {
            if (preg_match_all('/App\\\\Models\\\\[A-Za-z0-9_]+::'.$m.'\\s*\(/', $content, $mm)) {
                $total += count($mm[0]);
            }
        }
        if (preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z0-9_]+);/m', $content, $imports)) {
            $imported = array_unique($imports[1]);
            foreach ($imported as $short) {
                foreach ($methods as $m) {
                    if (preg_match_all('/(?<![A-Za-z0-9_])'.$short.'::'.$m.'\\s*\(/', $content, $mm)) {
                        $total += count($mm[0]);
                    }
                }
            }
        }
        return $total;
    }
}
