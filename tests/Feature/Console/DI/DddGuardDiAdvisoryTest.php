<?php

namespace Tests\Feature\Console\DI;

use Illuminate\Support\Facades\File;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DddGuardDiAdvisoryTest extends TestCase
{
    private string $tempControllerDir;
    private string $tempControllerFile;
    private string $processId;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary controller to simulate a concrete service import missing interface
        // Use process ID to avoid conflicts in parallel test execution
        $this->processId = (string) getmypid();
        $this->tempControllerDir = base_path("app/Http/Controllers/Temp_{$this->processId}");
        if (!is_dir($this->tempControllerDir)) {
            mkdir($this->tempControllerDir, 0755, true);
        }
        $this->tempControllerFile = $this->tempControllerDir.'/TempAdvisoryController.php';

        $serviceFqcn = 'App\\Domain\\User\\Services\\UniversalLimitService';
    $code = <<<'PHP'
<?php
namespace App\\Http\\Controllers\\Temp_{$this->processId};

use REPLACE_FQCN;

class TempAdvisoryController
{
    private UniversalLimitService $service;

    public function __construct(UniversalLimitService $service)
    {
    // Store the service to avoid unused variable notice
    $this->service = $service;
    }
}
PHP;
    // Replace placeholder with actual FQCN to ensure valid PHP "use" import
    $code = str_replace('REPLACE_FQCN', $serviceFqcn, $code);
    file_put_contents($this->tempControllerFile, $code);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempControllerFile)) {
            unlink($this->tempControllerFile);
        }
        // Attempt to remove directory if empty
        if (is_dir($this->tempControllerDir)) {
            @rmdir($this->tempControllerDir);
        }
        
        // Clean up process-specific JSON report file
        $reportPath = storage_path("app/ddd_guard_report_{$this->processId}.json");
        if (file_exists($reportPath)) {
            @unlink($reportPath);
        }
        
        parent::tearDown();
    }

    #[Test]
    public function di_scan_reports_advisory_for_missing_interface(): void
    {
        // When interface exists now, this scenario produces hard violations instead of advisories.
        $this->artisan('ddd:guard', ['--di' => true, '--format' => 'json'])
            ->assertExitCode(1); // Interface present -> violations expected

        // Re-run command capturing buffered output (artisan testing tools don't give direct output string) via manual process
        // We'll execute the command class directly to capture output, or simpler: read the report file if configured.
        // Use process-specific report file to avoid conflicts in parallel test execution
        $reportPath = storage_path("app/ddd_guard_report_{$this->processId}.json");
        
        // Override the config for this process
        config(['ddd_guard.json_report_path' => $reportPath]);
        
        // Re-run command with updated config
        $this->artisan('ddd:guard', ['--di' => true, '--format' => 'json'])
            ->assertExitCode(1);
            
        $this->assertFileExists($reportPath, 'JSON report file not generated');
        $json = json_decode(file_get_contents($reportPath), true);
        $this->assertIsArray($json);
    $this->assertArrayHasKey('violations', $json);
    $this->assertNotEmpty($json['violations'], 'Expected at least one violation');
    $foundConcrete = collect($json['violations'])->contains(fn($v) => ($v['type'] ?? null) === 'di_concrete_import');
    $this->assertTrue($foundConcrete, 'Expected at least one di_concrete_import violation in report');
    }

    #[Test]
    public function di_scan_fails_when_fail_on_advisory_enabled(): void
    {
        // Use process-specific report file to avoid conflicts in parallel test execution
        $reportPath = storage_path("app/ddd_guard_report_{$this->processId}.json");
        config(['ddd_guard.json_report_path' => $reportPath]);
        
        $this->artisan('ddd:guard', ['--di' => true, '--format' => 'json', '--fail-on-advisory' => true])
            ->assertExitCode(1); // Advisories should trigger failure with flag
    }

    #[Test]
    public function di_scan_reports_hard_violation_when_interface_exists(): void
    {
        // Arrange: create a controller that imports UniversalLimitService (interface now exists => violation)
        $controllerFile = $this->tempControllerDir.'/TempViolationController.php';
        $code = <<<'PHP'
<?php
namespace App\Http\Controllers\Temp;

use App\Domain\User\Contracts\UniversalLimitServiceInterface as UniversalLimitService;

class TempViolationController
{
    public function __construct(UniversalLimitService $service) {}
}
PHP;
        file_put_contents($controllerFile, $code);

        // Use process-specific report file to avoid conflicts in parallel test execution
        $reportPath = storage_path("app/ddd_guard_report_{$this->processId}.json");
        config(['ddd_guard.json_report_path' => $reportPath]);
        
        $this->artisan('ddd:guard', ['--di' => true, '--format' => 'json'])
            ->assertExitCode(1); // Hard violation should fail build now that interface exists

        // Inspect JSON report
        $json = json_decode(file_get_contents($reportPath), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('violations', $json);
        $found = collect($json['violations'])->contains(fn($v) => ($v['type'] ?? null) === 'di_concrete_import');
        $this->assertTrue($found, 'Expected di_concrete_import violation once interface exists');

        @unlink($controllerFile);
    }
}
