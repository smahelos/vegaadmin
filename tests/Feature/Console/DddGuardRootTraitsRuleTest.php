<?php

namespace Tests\Feature\Console;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Guard test ensuring legacy root Traits directory remains absent.
 */
class DddGuardRootTraitsRuleTest extends TestCase
{
    private string $traitsPath;
    private Filesystem $fs;
    private string $processId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem();
        // Use process-specific directory to avoid conflicts in parallel test execution
        $this->processId = (string) getmypid();
        $this->traitsPath = base_path("app/Traits_{$this->processId}");
    }

    #[Test]
    public function traits_root_directory_is_absent(): void
    {
        // Test the original path, not our process-specific one
        $originalTraitsPath = base_path('app/Traits');
        $this->assertFalse(is_dir($originalTraitsPath), 'Legacy app/Traits directory must stay removed.');
    }

    #[Test]
    public function ddd_guard_flags_reintroduced_traits_directory(): void
    {
        // Create a temporary directory + file to simulate regression
        $this->fs->makeDirectory($this->traitsPath, 0755, true, true);
        $dummyFile = $this->traitsPath . '/LegacyDummyTrait.php';
        $this->fs->put($dummyFile, "<?php\ntrait LegacyDummyTrait {}\n");

        // Temporarily override config to use our process-specific path
        $originalConfig = config('ddd_guard.forbidden_paths');
        $modifiedConfig = collect($originalConfig)->map(function($path) {
            if (isset($path['path']) && $path['path'] === base_path('app/Traits')) {
                $path['path'] = $this->traitsPath;
            }
            return $path;
        })->toArray();
        
        config(['ddd_guard.forbidden_paths' => $modifiedConfig]);

        // Run guard
        $exitCode = \Artisan::call('ddd:guard', ['--format' => 'json']);
        $output = \Artisan::output();

        // Restore original config
        config(['ddd_guard.forbidden_paths' => $originalConfig]);

        // Cleanup immediately to avoid side-effects for other tests
        $this->fs->delete($dummyFile);
        $this->fs->deleteDirectory($this->traitsPath);

        $this->assertSame(1, $exitCode, 'ddd:guard should fail when app/Traits reappears.');
        $this->assertStringContainsString('Traits', $output, 'Guard JSON should mention Traits path.');
    }
}
