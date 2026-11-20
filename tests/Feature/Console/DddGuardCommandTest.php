<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DddGuardCommandTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        // Outside the repository: the guard of a parallel test process scans the repository
        $this->directory = sys_get_temp_dir() . '/billex-ddd-guard-' . uniqid('', true);
        File::makeDirectory($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    #[Test]
    public function guard_passes_on_current_code(): void
    {
        $command = $this->pendingCommand('ddd:guard');
        $command->expectsOutput('No DDD guard violations detected.');
        $command->assertExitCode(0);
        $command->run();
    }

    #[Test]
    public function guard_reports_new_file_in_frozen_directory(): void
    {
        File::put($this->directory . '/NewService.php', "<?php\n");

        $report = $this->runGuard(
            ['ddd_guard.forbidden_paths' => [$this->rule('no_new_files')]],
            [],
            1,
        );

        $this->assertSame('new_file', $report['violations'][0]['type']);
    }

    #[Test]
    public function guard_reports_existing_forbidden_directory(): void
    {
        $report = $this->runGuard(
            ['ddd_guard.forbidden_paths' => [$this->rule('must_not_exist')]],
            [],
            1,
        );

        $this->assertSame('forbidden_directory', $report['violations'][0]['type']);
    }

    #[Test]
    public function di_scan_reports_violation_for_concrete_service_with_interface(): void
    {
        $this->writeControllerImporting('App\Domain\User\Services\LocaleService');

        $report = $this->runGuard(
            [],
            ['--di' => true],
            1,
        );

        $this->assertSame('di_concrete_import', $report['violations'][0]['type']);
    }

    #[Test]
    public function di_scan_reports_advisory_for_concrete_service_without_interface(): void
    {
        $this->writeControllerImporting('App\Domain\User\Services\UnboundService');

        $report = $this->runGuard(
            [],
            ['--di' => true],
            0,
        );

        $this->assertSame([], $report['violations']);
        $this->assertSame('di_missing_interface', $report['advisories'][0]['type']);
    }

    #[Test]
    public function di_scan_fails_on_advisory_when_fail_on_advisory_enabled(): void
    {
        $this->writeControllerImporting('App\Domain\User\Services\UnboundService');

        $report = $this->runGuard(
            [],
            ['--di' => true, '--fail-on-advisory' => true],
            1,
        );

        $this->assertSame([], $report['violations']);
        $this->assertSame('di_missing_interface', $report['advisories'][0]['type']);
    }

    /**
     * Runs the guard with only the given rules, over the test's own directory.
     *
     * @param  array<string, mixed>  $config
     * @param  array<string, bool>  $options
     * @return array{violations: list<array{type: string}>, advisories: list<array{type: string}>}
     */
    private function runGuard(
        array $config,
        array $options,
        int $exitCode,
    ): array {
        $reportPath = $this->directory . '/report.json';
        config(
            [
                'ddd_guard.forbidden_paths' => [],
                'ddd_guard.forbidden_patterns' => [],
                'ddd_guard.di_scan_paths' => [$this->directory],
                'ddd_guard.json_report_path' => $reportPath,
                ...$config,
            ],
        );

        $command = $this->pendingCommand('ddd:guard', ['--format' => 'json', ...$options]);
        $command->assertExitCode($exitCode);
        $command->run();

        return json_decode(
            File::get($reportPath),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @return array{path: string, allowed_files: list<string>, allowed_directories: list<string>, mode: string}
     */
    private function rule(string $mode): array
    {
        return [
            'path' => $this->directory,
            'allowed_files' => [],
            'allowed_directories' => [],
            'mode' => $mode,
        ];
    }

    private function writeControllerImporting(string $serviceClass): void
    {
        File::put(
            $this->directory . '/TempController.php',
            "<?php\nnamespace App\\Http\\Controllers\\Temp;\n\nuse {$serviceClass};\n\nclass TempController {}\n",
        );
    }
}
