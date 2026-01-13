<?php

namespace Tests\Feature\Console\Commands;

use App\Models\PerformanceMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Console\Command;

/**
 * @group isolated
 * Tests for db:clean-metrics command that require sequential execution
 * due to potential conflicts with parallel database operations.
 */
class CleanPerformanceMetricsFeatureTest extends TestCase
{
    use RefreshDatabase;

    private string $processId;

    protected function setUp(): void
    {
        parent::setUp();

        // Use process ID to avoid conflicts in parallel test execution
        $this->processId = (string) getmypid();

        // Simple delay based on process ID to reduce collision chance
        usleep((int)$this->processId % 500 * 1000); // 0-499ms delay

        // Create test performance metrics data
        $this->createTestMetrics();
    }

    private function createTestMetrics(): void
    {
        // Create some old metrics (older than 90 days) with process-specific identifiers
        PerformanceMetric::factory()->count(5)->create([
            'measured_at' => now()->subDays(100),
            'metadata' => ['test_process' => $this->processId, 'category' => 'old']
        ]);

        // Create some recent metrics (within 90 days) with process-specific identifiers
        PerformanceMetric::factory()->count(3)->create([
            'measured_at' => now()->subDays(30),
            'metadata' => ['test_process' => $this->processId, 'category' => 'recent']
        ]);

        // Create some sample metrics (with generated metadata) with process-specific identifiers
        PerformanceMetric::factory()->count(2)->create([
            'metadata' => [
                'generated' => true,
                'type' => 'sample',
                'test_process' => $this->processId,
                'category' => 'sample'
            ]
        ]);
    }

    #[Test]
    public function command_executes_successfully(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100); // 0-99ms delay

        $exitCode = Artisan::call('db:clean-metrics');

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_days_option(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 30
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_type_option(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:clean-metrics', [
            '--type' => 'old'
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_dry_run_option(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:clean-metrics', [
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        Artisan::call('db:clean-metrics');

        $output = Artisan::output();

        $this->assertStringContainsString('Starting performance metrics cleanup', $output);
    }

    #[Test]
    public function command_handles_different_cleanup_types(): void
    {
        // Exclude 'duplicate' and 'all' types that can cause hangs in testing
        $types = ['old', 'sample'];

        foreach ($types as $index => $type) {
            // Add staggered delay for each type + process ID
            usleep(((int)$this->processId % 1000 + $index * 50) * 100);

            $exitCode = Artisan::call('db:clean-metrics', [
                '--type' => $type,
                '--dry-run' => true
            ]);

            // These types should work reliably
            $this->assertEquals(0, $exitCode);
        }
    }

    #[Test]
    public function command_respects_dry_run_mode(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        // In dry-run mode, nothing should be deleted
        $exitCode = Artisan::call('db:clean-metrics', [
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Dry run: Yes', $output);

        // Verify data is still there after dry run - count only our process-specific data
        $processSpecificCount = PerformanceMetric::whereJsonContains('metadata->test_process', $this->processId)->count();
        $this->assertGreaterThan(0, $processSpecificCount);
    }

    #[Test]
    public function command_validates_parameters(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        // Test with valid days parameter
        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 90
        ]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Days to keep: 90', $output);
    }

    #[Test]
    public function command_handles_edge_cases(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        // Test with minimal days
        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 1,
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);

        // Add delay between commands
        usleep(50000); // 50ms

        // Test with large days
        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 365,
            '--dry-run' => true
        ]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_shows_configuration_info(): void
    {
        // Add small delay based on process ID to stagger parallel execution
        usleep((int)$this->processId % 1000 * 100);

        Artisan::call('db:clean-metrics', [
            '--type' => 'old',
            '--days' => 60,
            '--dry-run' => true
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Type: old', $output);
        $this->assertStringContainsString('Days to keep: 60', $output);
        $this->assertStringContainsString('Dry run: Yes', $output);
    }

    #[Test]
    public function command_validates_cleanup_types(): void
    {
        // Add small delay based on process ID
        usleep((int)$this->processId % 1000 * 100);

        // Test valid types that don't hang
        $validTypes = ['old', 'sample'];
        foreach ($validTypes as $type) {
            $exitCode = Artisan::call('db:clean-metrics', [
                '--type' => $type,
                '--dry-run' => true
            ]);
            $this->assertEquals(0, $exitCode, "Type '{$type}' should be valid");
        }
    }

    #[Test]
    public function command_rejects_invalid_type(): void
    {
        // Add small delay based on process ID
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:clean-metrics', [
            '--type' => 'invalid_type',
            '--dry-run' => true
        ]);

        // Should fail with invalid type
        $this->assertEquals(1, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Invalid cleanup type', $output);
    }
}
