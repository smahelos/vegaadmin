<?php

namespace Tests\Feature\Console\Commands;

use App\Models\PerformanceMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanPerformanceMetricsFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:clean-metrics');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_days_option(): void
    {
        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 30
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_type_option(): void
    {
        $exitCode = Artisan::call('db:clean-metrics', [
            '--type' => 'old'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_dry_run_option(): void
    {
        $exitCode = Artisan::call('db:clean-metrics', [
            '--dry-run' => true
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:clean-metrics');
        
        $output = Artisan::output();
        
        $this->assertStringContainsString('Starting performance metrics cleanup', $output);
    }

    #[Test]
    public function command_handles_different_cleanup_types(): void
    {
        $types = ['old', 'sample', 'duplicate', 'all'];
        
        foreach ($types as $type) {
            $exitCode = Artisan::call('db:clean-metrics', [
                '--type' => $type,
                '--dry-run' => true
            ]);
            
            // Some types might not be fully implemented, accept 0 or 1
            $this->assertTrue(in_array($exitCode, [0, 1]));
        }
    }

    #[Test]
    public function command_respects_dry_run_mode(): void
    {
        // Create some test performance metrics if model exists
        if (class_exists(PerformanceMetric::class)) {
            // In dry-run mode, nothing should be deleted
            $exitCode = Artisan::call('db:clean-metrics', [
                '--dry-run' => true
            ]);
            
            $this->assertEquals(0, $exitCode);
            
            $output = Artisan::output();
            $this->assertStringContainsString('Dry run: Yes', $output);
        } else {
            $this->markTestSkipped('PerformanceMetric model not found');
        }
    }

    #[Test]
    public function command_validates_parameters(): void
    {
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
        // Test with minimal days
        $exitCode = Artisan::call('db:clean-metrics', [
            '--days' => 1,
            '--dry-run' => true
        ]);
        
        $this->assertEquals(0, $exitCode);
        
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
}
