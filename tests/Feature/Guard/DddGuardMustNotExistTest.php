<?php

namespace Tests\Feature\Guard;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for must_not_exist guard rules defined in config/ddd_guard.php.
 * Ensures the guard passes when forbidden directories are absent and fails with a clear violation when recreated.
 */
class DddGuardMustNotExistTest extends TestCase
{
    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
    }

    #[Test]
    public function guard_passes_when_forbidden_directories_are_absent(): void
    {
        // Pre-condition: ensure directory truly absent
        $forbidden = base_path('app/Repositories');
        if (is_dir($forbidden)) {
            $this->files->deleteDirectory($forbidden);
        }

        $exitCode = Artisan::call('ddd:guard');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, 'Guard should exit with code 0 when forbidden directory is absent. Output: '.$output);
        // When translations missing, key is returned; accept either translated phrase or key fragment.
        $this->assertStringNotContainsString('forbidden_directory', $output, 'No forbidden_directory violation expected.');
        $this->assertStringNotContainsString('Directory must not exist', $output, 'No must_not_exist violation message expected.');
    }

    #[Test]
    public function guard_fails_when_forbidden_directory_is_created(): void
    {
        $forbidden = base_path('app/Repositories');
        // Create directory + dummy file
        if (!is_dir($forbidden)) {
            $this->files->makeDirectory($forbidden, 0777, true);
        }
        $dummy = $forbidden.'/DummyRepository.php';
        file_put_contents($dummy, "<?php\n// dummy legacy repo file for guard regression test\n");

        try {
            $exitCode = Artisan::call('ddd:guard');
            $output = Artisan::output();

            $this->assertSame(1, $exitCode, 'Guard should fail (exit 1) when forbidden directory exists. Output: '.$output);
            $this->assertStringContainsString('forbidden_directory', $output, 'Expected violation type not found in output.');
            $this->assertStringContainsString('Directory must not exist (legacy root): Repositories', $output, 'Expected must_not_exist violation message not found.');
        } finally {
            // Cleanup to not affect other tests
            if (is_dir($forbidden)) {
                $this->files->deleteDirectory($forbidden);
            }
        }
    }
}
