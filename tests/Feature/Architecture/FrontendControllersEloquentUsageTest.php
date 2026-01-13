<?php

namespace Tests\Feature\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Ensures no Frontend controller exceeds 1 direct Eloquent static call (thin controller rule).
 * Baseline controllers listed in storage/app/metrics/frontend_controller_eloquent_baseline.json.
 */
class FrontendControllersEloquentUsageTest extends TestCase
{
    private array $baselineControllers;
    private int $maxAllowed;

    protected function setUp(): void
    {
        parent::setUp();
        $path = storage_path('app/metrics/frontend_controller_eloquent_baseline.json');
        $this->assertFileExists($path, 'Missing baseline JSON: generate or commit frontend_controller_eloquent_baseline.json');
        $json = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->baselineControllers = $json['controllers'];
        $this->maxAllowed = $json['rule']['max_eloquent_static_calls'] ?? 1;
    }

    #[Test]
    public function frontend_controllers_do_not_exceed_direct_eloquent_call_threshold(): void
    {
        $violations = [];
        foreach ($this->baselineControllers as $relPath) {
            $full = app_path($relPath ? str_replace('Http/Controllers', '/Http/Controllers', $relPath) : '');
            // Safer resolution
            $full = app_path($relPath);
            if (!file_exists($full)) {
                // New / removed controller -> skip (does not constitute violation)
                continue;
            }
            $content = file_get_contents($full);
            $count = $this->countEloquentStaticCalls($content);
            if ($count > $this->maxAllowed) {
                $violations[] = $relPath . " has $count direct Eloquent static calls (limit {$this->maxAllowed})";
            }
        }

        if (!empty($violations)) {
            $this->fail("Frontend controller thinness violations:\n".implode("\n", $violations));
        }
        $this->assertTrue(true);
    }

    private function countEloquentStaticCalls(string $content): int
    {
        $methods = ['where','create','find','all','first','update','delete','pluck','paginate','orderBy','count','sum','avg','min','max'];
        $total = 0;

        // Count fully-qualified static calls (App\Models\Model::method())
        foreach ($methods as $m) {
            if (preg_match_all('/App\\\\Models\\\\[A-Za-z0-9_]+::' . $m . '\\s*\(/', $content, $mm)) {
                $total += count($mm[0]);
            }
        }

        // Detect imported model short names via use statements and count ShortName::method() occurrences
        if (preg_match_all('/^use\s+App\\\\Models\\\\([A-Za-z0-9_]+);/m', $content, $imports)) {
            $imported = array_unique($imports[1]);
            foreach ($imported as $short) {
                foreach ($methods as $m) {
                    $pattern = '/(?<![A-Za-z0-9_])' . $short . '::' . $m . '\\s*\(/';
                    if (preg_match_all($pattern, $content, $mm)) {
                        $total += count($mm[0]);
                    }
                }
            }
        }

        return $total;
    }
}
