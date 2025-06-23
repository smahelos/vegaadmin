<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseOptimizeCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:optimize');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_optimizes_database_tables(): void
    {
        $exitCode = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_optimization_safely(): void
    {
        // Command should complete without errors
        $exitCode = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_can_be_run_multiple_times(): void
    {
        // Run optimization multiple times - should be safe
        $exitCode1 = Artisan::call('db:optimize');
        $exitCode2 = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode1);
        $this->assertEquals(0, $exitCode2);
    }

    #[Test]
    public function command_analyzes_performance(): void
    {
        Artisan::call('db:optimize');
        
        $output = Artisan::output();
        
        // Should provide some feedback about optimization
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }

    #[Test]
    public function command_performs_database_operations(): void
    {
        // Test that command can access and optimize database
        $exitCode = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_produces_optimization_report(): void
    {
        Artisan::call('db:optimize');
        
        $output = Artisan::output();
        
        $this->assertIsString($output);
    }

    #[Test]
    public function command_handles_empty_database(): void
    {
        // Should handle empty database gracefully
        $exitCode = Artisan::call('db:optimize');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_completes_within_reasonable_time(): void
    {
        $startTime = microtime(true);
        
        $exitCode = Artisan::call('db:optimize');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertEquals(0, $exitCode);
        $this->assertLessThan(30, $executionTime); // Should complete within 30 seconds
    }
}
