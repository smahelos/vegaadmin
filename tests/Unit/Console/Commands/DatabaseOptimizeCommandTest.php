<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\DatabaseOptimizeCommand;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseOptimizeCommandTest extends TestCase
{
    private DatabaseOptimizeCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new DatabaseOptimizeCommand();
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
        $this->assertStringContainsString('db:optimize', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('database', $description);
        $this->assertStringContainsString('Optimize', $description);
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
    public function command_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->command);
        
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasMethod('handle'));
    }
}
