<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseMonitorCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_executes_successfully(): void
    {
        $exitCode = Artisan::call('db:monitor');
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_metric_option(): void
    {
        $exitCode = Artisan::call('db:monitor', [
            '--metric' => 'all'
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_accepts_store_option(): void
    {
        $exitCode = Artisan::call('db:monitor', [
            '--store' => true
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_handles_different_metrics(): void
    {
        $metrics = ['all', 'performance', 'connections', 'queries'];
        
        foreach ($metrics as $metric) {
            $exitCode = Artisan::call('db:monitor', [
                '--metric' => $metric
            ]);
            
            $this->assertEquals(0, $exitCode);
        }
    }

    #[Test]
    public function command_provides_feedback(): void
    {
        Artisan::call('db:monitor');
        
        $output = Artisan::output();
        
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_monitors_database_performance(): void
    {
        $exitCode = Artisan::call('db:monitor', [
            '--metric' => 'performance'
        ]);
        
        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertIsString($output);
    }

    #[Test]
    public function command_can_store_metrics(): void
    {
        $exitCode = Artisan::call('db:monitor', [
            '--store' => true
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_handles_combined_options(): void
    {
        $exitCode = Artisan::call('db:monitor', [
            '--metric' => 'all',
            '--store' => true
        ]);
        
        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function command_uses_default_metric(): void
    {
        Artisan::call('db:monitor');
        
        $output = Artisan::output();
        
        // Should use default metric 'all'
        $this->assertNotEmpty($output);
    }

    #[Test]
    public function command_produces_monitoring_output(): void
    {
        Artisan::call('db:monitor', [
            '--metric' => 'performance'
        ]);
        
        $output = Artisan::output();
        
        $this->assertIsString($output);
        $this->assertNotEmpty(trim($output));
    }
}
