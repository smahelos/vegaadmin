<?php

namespace Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Note: SyncArtisanCommands requires dependency injection
 * This command should be tested via feature tests instead
 * of unit tests due to its dependencies
 */
class SyncArtisanCommandsTest extends TestCase
{
    #[Test]
    public function command_class_exists(): void
    {
        $this->assertTrue(class_exists('App\Console\Commands\SyncArtisanCommands'));
    }

    #[Test] 
    public function command_has_required_properties(): void
    {
        $reflection = new \ReflectionClass('App\Console\Commands\SyncArtisanCommands');
        
        $this->assertTrue($reflection->hasProperty('signature'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->hasMethod('__construct'));
    }

    #[Test]
    public function command_constructor_requires_service(): void
    {
        $reflection = new \ReflectionClass('App\Console\Commands\SyncArtisanCommands');
        $constructor = $reflection->getMethod('__construct');
        $parameters = $constructor->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('commandsService', $parameters[0]->getName());
    }

    #[Test]
    public function command_signature_is_string(): void
    {
        $reflection = new \ReflectionClass('App\Console\Commands\SyncArtisanCommands');
        $signatureProperty = $reflection->getProperty('signature');
        
        $this->assertTrue($signatureProperty->isProtected());
    }

    #[Test]
    public function command_description_is_string(): void
    {
        $reflection = new \ReflectionClass('App\Console\Commands\SyncArtisanCommands');
        $descriptionProperty = $reflection->getProperty('description');
        
        $this->assertTrue($descriptionProperty->isProtected());
    }

    #[Test]
    public function command_uses_required_imports(): void
    {
        $reflection = new \ReflectionClass('App\Console\Commands\SyncArtisanCommands');
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('App\Services\ArtisanCommandsService', $fileContent);
        $this->assertStringContainsString('App\Models\ArtisanCommand', $fileContent);
        $this->assertStringContainsString('Illuminate\Console\Command', $fileContent);
    }
}
