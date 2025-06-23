<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CleanPerformanceMetrics;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanPerformanceMetricsTest extends TestCase
{
    private CleanPerformanceMetrics $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new CleanPerformanceMetrics();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        $this->assertIsString($signature);
        $this->assertStringContainsString('db:clean-metrics', $signature);
        $this->assertStringContainsString('--days=90', $signature);
        $this->assertStringContainsString('--type=old', $signature);
        $this->assertStringContainsString('--dry-run', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('performance metrics', $description);
        $this->assertStringContainsString('database bloat', $description);
    }

    #[Test]
    public function command_has_handle_method(): void
    {
        $this->assertTrue(method_exists($this->command, 'handle'));
        
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function handle_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('int', $returnType->getName());
    }

    #[Test]
    public function command_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        // Check that class is properly structured
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasMethod('handle'));
        
        // Check properties are protected
        $signatureProperty = $reflection->getProperty('signature');
        $descriptionProperty = $reflection->getProperty('description');
        
        $this->assertTrue($signatureProperty->isProtected());
        $this->assertTrue($descriptionProperty->isProtected());
    }

    #[Test]
    public function command_has_default_option_values(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        // Check default values are present
        $this->assertStringContainsString('days=90', $signature);
        $this->assertStringContainsString('type=old', $signature);
    }

    #[Test]
    public function command_supports_required_options(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        // Check all required options are present
        $this->assertStringContainsString('--days', $signature);
        $this->assertStringContainsString('--type', $signature);
        $this->assertStringContainsString('--dry-run', $signature);
    }

    #[Test]
    public function command_uses_performance_metric_model(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('App\Models\PerformanceMetric', $fileContent);
        $this->assertStringContainsString('use App\Models\PerformanceMetric;', $fileContent);
    }
}
