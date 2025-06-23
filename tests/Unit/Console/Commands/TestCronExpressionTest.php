<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TestCronExpression;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestCronExpressionTest extends TestCase
{
    private TestCronExpression $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->command = new TestCronExpression();
    }

    #[Test]
    public function command_extends_console_command(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    #[Test]
    public function command_has_correct_signature(): void
    {
        $expectedSignature = 'cron:test {id? : tasd ID to test} {--expression= : CRON expression to test}';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('signature');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedSignature, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $expectedDescription = 'Tests cron expression and shows time of next run.';
        
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('description');
        $property->setAccessible(true);
        
        $this->assertEquals($expectedDescription, $property->getValue($this->command));
    }

    #[Test]
    public function command_has_handle_method(): void
    {
        $this->assertTrue(method_exists($this->command, 'handle'));
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
        
        // Check that class uses proper namespace
        $this->assertEquals('App\Console\Commands', $reflection->getNamespaceName());
        
        // Check that signature property exists and is protected
        $signatureProperty = $reflection->getProperty('signature');
        $this->assertTrue($signatureProperty->isProtected());
        
        // Check that description property exists and is protected  
        $descriptionProperty = $reflection->getProperty('description');
        $this->assertTrue($descriptionProperty->isProtected());
    }

    #[Test]
    public function command_has_helper_methods(): void
    {
        $this->assertTrue(method_exists($this->command, 'convertFrequencyToExpression'));
    }

    #[Test]
    public function command_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileName = $reflection->getFileName();
        $content = file_get_contents($fileName);
        
        $this->assertStringContainsString('use Illuminate\Console\Command;', $content);
        $this->assertStringContainsString('use App\Models\CronTask;', $content);
        $this->assertStringContainsString('use Carbon\Carbon;', $content);
        $this->assertStringContainsString('use Cron\CronExpression as CronParser;', $content);
    }

    #[Test]
    public function command_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $docComment = $reflection->getDocComment();
        
        $this->assertNotEmpty($docComment);
    }

    #[Test]
    public function command_has_private_helper_methods(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        $convertMethod = $reflection->getMethod('convertFrequencyToExpression');
        $this->assertTrue($convertMethod->isPrivate());
    }
}
