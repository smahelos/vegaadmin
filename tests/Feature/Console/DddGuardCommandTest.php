<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DddGuardCommandTest extends TestCase
{
    private string $processId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processId = (string) getmypid();
    }
    #[Test]
    public function guard_passes_on_baseline_allowlist(): void
    {
        $allow = config('ddd_guard.forbidden_paths')[0]['allowed_files'];
        $servicesPath = base_path('app/Services');
        if (is_dir($servicesPath)) {
            foreach (File::files($servicesPath) as $file) {
                $base = $file->getFilename();
                $this->assertContains($base, $allow, 'Baseline contains an unallowlisted legacy file: '.$base);
            }
        }

        $this->artisan('ddd:guard')
            ->expectsOutput(trans('ddd_guard.no_violations'))
            ->assertExitCode(0);
    }

    #[Test]
    public function guard_detects_new_legacy_service_file(): void
    {
        // Use process-specific directory to avoid conflicts in parallel test execution
        $servicesPath = base_path("app/Services_{$this->processId}");
        if (!is_dir($servicesPath)) {
            File::makeDirectory($servicesPath, 0755, true);
        }
        $tempFile = $servicesPath.'/TemporaryNewService.php';
        File::put($tempFile, "<?php\nnamespace App\\Services;\nclass TemporaryNewService {}\n");
        
        // Temporarily override config to use our process-specific path
        $originalConfig = config('ddd_guard.forbidden_paths');
        $originalDiPaths = config('ddd_guard.di_scan_paths');
        
        $modifiedConfig = collect($originalConfig)->map(function($path) use ($servicesPath) {
            if (isset($path['path']) && $path['path'] === base_path('app/Services')) {
                $path['path'] = $servicesPath;
            }
            return $path;
        })->toArray();
        
        // Disable DI scanning to avoid conflicts with process-specific Controllers folders
        config([
            'ddd_guard.forbidden_paths' => $modifiedConfig,
            'ddd_guard.di_enabled' => false
        ]);
        
        try {
            $this->artisan('ddd:guard')
                ->expectsOutput(trans('ddd_guard.violations_found'))
                ->assertExitCode(1);
        } finally {
            // Restore original config
            config([
                'ddd_guard.forbidden_paths' => $originalConfig,
                'ddd_guard.di_scan_paths' => $originalDiPaths,
                'ddd_guard.di_enabled' => true
            ]);
            File::delete($tempFile);
            File::deleteDirectory($servicesPath);
        }
    }
}
