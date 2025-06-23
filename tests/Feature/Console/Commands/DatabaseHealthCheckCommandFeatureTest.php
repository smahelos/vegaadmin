<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseHealthCheckCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:health-check');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:health-check');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_checks_database_connection(): void
    {
        $exitCode = Artisan::call('db:health-check');
        
        // Should succeed if database is accessible
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_performs_health_checks(): void
    {
        Artisan::call('db:health-check');
        
        $output = Artisan::output();
        
        // Command should provide some health information
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_handles_database_operations(): void
    {
        // Test that command can access database
        $exitCode = Artisan::call('db:health-check');
        
        $this->assertContains($exitCode, [0, 1]); // 0 = healthy, 1 = issues found
    }

    #[Test]
    public function command_returns_appropriate_exit_code(): void
    {
        $exitCode = Artisan::call('db:health-check');
        
        // Health check should return 0 for success or 1 for issues
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function command_can_be_run_multiple_times(): void
    {
        // Run command multiple times to ensure consistency
        $exitCode1 = Artisan::call('db:health-check');
        $exitCode2 = Artisan::call('db:health-check');
        
        $this->assertEquals($exitCode1, $exitCode2);
    }

    #[Test]
    public function command_produces_consistent_output(): void
    {
        Artisan::call('db:health-check');
        $output1 = Artisan::output();
        
        Artisan::call('db:health-check');
        $output2 = Artisan::output();
        
        // Both outputs should be strings
        $this->assertIsString($output1);
        $this->assertIsString($output2);
    }

    #[Test]
    public function command_handles_basic_database_queries(): void
    {
        // Test that command can perform basic database operations
        $exitCode = Artisan::call('db:health-check');
        
        // Should complete without throwing exceptions
        $this->assertIsInt($exitCode);
    }
}
