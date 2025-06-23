<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\DatabaseArchiveCommand;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DatabaseArchiveCommandTest extends TestCase
{
    private DatabaseArchiveCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new DatabaseArchiveCommand();
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
        $this->assertStringContainsString('db:archive', $signature);
        $this->assertStringContainsString('--table=invoices', $signature);
        $this->assertStringContainsString('--dry-run', $signature);
        $this->assertStringContainsString('--force', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('Archive old records', $description);
        $this->assertStringContainsString('retention policies', $description);
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
    public function command_uses_database_operations(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('Illuminate\Support\Facades\DB', $fileContent);
        $this->assertStringContainsString('Carbon\Carbon', $fileContent);
    }

    #[Test]
    public function command_has_default_table_option(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        // Check default table value
        $this->assertStringContainsString('table=invoices', $signature);
    }

    #[Test]
    public function command_supports_safety_options(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);
        
        $signature = $signatureProperty->getValue($this->command);
        
        // Check safety options are present
        $this->assertStringContainsString('--dry-run', $signature);
        $this->assertStringContainsString('--force', $signature);
    }
}
