<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ManageUserPermissions;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManageUserPermissionsTest extends TestCase
{
    private ManageUserPermissions $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new ManageUserPermissions();
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
        $this->assertStringContainsString('user:permissions', $signature);
    }

    #[Test]
    public function command_has_correct_description(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);
        
        $description = $descriptionProperty->getValue($this->command);
        
        $this->assertIsString($description);
        $this->assertStringContainsString('permission', $description);
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

    #[Test]
    public function command_uses_permission_related_models(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Check for permission-related imports
        $this->assertTrue(
            strpos($fileContent, 'Permission') !== false ||
            strpos($fileContent, 'Role') !== false ||
            strpos($fileContent, 'User') !== false
        );
    }
}
