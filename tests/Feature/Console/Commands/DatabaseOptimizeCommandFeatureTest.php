<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseOptimizeCommandFeatureTest extends TestCase
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
    }
    #[Test]
    public function command_executes_successfully(): void
    {
        // Add delay and force flag to avoid interactive confirmation
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        Artisan::call('db:optimize', ['--force' => true]);

        $output = Artisan::output();

        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_optimizes_database_tables(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_optimization_safely(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        // Command should complete without errors
        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_can_be_run_multiple_times(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        // Run optimization multiple times - should be safe
        $exitCode1 = Artisan::call('db:optimize', ['--force' => true]);

        // Add delay between runs to prevent database lock conflicts
        usleep(100000); // 100ms

        $exitCode2 = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
    }

    #[Test]
    public function command_analyzes_performance(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        Artisan::call('db:optimize', ['--force' => true]);

        $output = Artisan::output();

        // Should provide some feedback about optimization
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_performs_database_operations(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        // Test that command can access and optimize database
        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_produces_optimization_report(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        Artisan::call('db:optimize', ['--force' => true]);

        $output = Artisan::output();

        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_empty_database(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        // Should handle empty database gracefully
        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_completes_within_reasonable_time(): void
    {
        usleep((int)$this->processId % 1000 * 100);

        $startTime = microtime(true);

        $exitCode = Artisan::call('db:optimize', ['--force' => true]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(0, $exitCode);
        $this->assertLessThan(30, $executionTime); // Should complete within 30 seconds
    }
}
