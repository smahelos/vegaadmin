<?php

namespace Tests\Unit\Services;

use App\Services\ArtisanCommandsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanCommandsServiceUnitTest extends TestCase
{
    private ArtisanCommandsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArtisanCommandsService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ArtisanCommandsService::class, $this->service);
    }

    #[Test]
    public function service_has_get_all_commands_method(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCommands'));
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCommands');
        
        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function get_all_commands_method_has_correct_signature(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCommands');
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('onlyNames', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->hasType());
        $this->assertEquals('bool', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());
        $this->assertFalse($parameters[0]->getDefaultValue());
    }

    #[Test]
    public function get_all_commands_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCommands');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function service_has_proper_docblock(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $getAllCommandsMethod = $reflection->getMethod('getAllCommands');
        $docComment = $getAllCommandsMethod->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@param bool $onlyNames', $docComment);
        $this->assertStringContainsString('@return array', $docComment);
    }

    #[Test]
    public function service_uses_required_dependencies(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $fileContent = file_get_contents($reflection->getFileName());
        
        $this->assertStringContainsString('App\Models\ArtisanCommand', $fileContent);
        $this->assertStringContainsString('App\Models\ArtisanCommandCategory', $fileContent);
        $this->assertStringContainsString('Illuminate\Support\Facades\Artisan', $fileContent);
        $this->assertStringContainsString('Illuminate\Support\Facades\Cache', $fileContent);
    }

    #[Test]
    public function service_structure_validation(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Check that class is properly structured
        $this->assertTrue($reflection->hasMethod('getAllCommands'));
        
        // Check class is in correct namespace
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
    }

    #[Test]
    public function service_has_cache_functionality(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $fileContent = file_get_contents($reflection->getFileName());
        
        // Check for cache usage
        $this->assertStringContainsString('Cache::remember', $fileContent);
        $this->assertStringContainsString('artisan_commands_list', $fileContent);
    }

    #[Test]
    public function service_handles_parameter_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCommands');
        $parameters = $method->getParameters();
        
        $onlyNamesParam = $parameters[0];
        
        $this->assertTrue($onlyNamesParam->hasType());
        $this->assertEquals('bool', $onlyNamesParam->getType()->getName());
        $this->assertTrue($onlyNamesParam->isOptional());
    }
}
