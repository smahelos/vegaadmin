<?php

namespace Tests\Unit\Services;

use App\Services\ArtisanCommandsService;
use App\Contracts\ArtisanCommandsServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArtisanCommandsServiceTest extends TestCase
{
    #[Test]
    public function service_can_be_instantiated(): void
    {
        $service = new ArtisanCommandsService();
        $this->assertInstanceOf(ArtisanCommandsService::class, $service);
        $this->assertInstanceOf(ArtisanCommandsServiceInterface::class, $service);
    }

    #[Test]
    public function service_has_get_all_commands_method(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $this->assertTrue($reflection->hasMethod('getAllCommands'));
        
        $method = $reflection->getMethod('getAllCommands');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_all_commands_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $method = $reflection->getMethod('getAllCommands');
        
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        
        $param = $parameters[0];
        $this->assertEquals('onlyNames', $param->getName());
        $this->assertTrue($param->hasType());
        $this->assertEquals('bool', $param->getType()->getName());
        $this->assertTrue($param->isDefaultValueAvailable());
        $this->assertFalse($param->getDefaultValue());
    }

    #[Test]
    public function service_has_get_commands_by_category_method(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $this->assertTrue($reflection->hasMethod('getCommandsByCategory'));
        
        $method = $reflection->getMethod('getCommandsByCategory');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_commands_by_category_method_has_correct_parameters(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $method = $reflection->getMethod('getCommandsByCategory');
        
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        
        $param1 = $parameters[0];
        $this->assertEquals('categorySlug', $param1->getName());
        $this->assertTrue($param1->hasType());
        $this->assertEquals('?string', (string)$param1->getType());
        $this->assertTrue($param1->isDefaultValueAvailable());
        $this->assertNull($param1->getDefaultValue());
        
        $param2 = $parameters[1];
        $this->assertEquals('withoutCategory', $param2->getName());
        $this->assertTrue($param2->hasType());
        $this->assertEquals('bool', $param2->getType()->getName());
        $this->assertTrue($param2->isDefaultValueAvailable());
        $this->assertFalse($param2->getDefaultValue());
    }

    #[Test]
    public function service_has_get_all_categories_method(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $this->assertTrue($reflection->hasMethod('getAllCategories'));
        
        $method = $reflection->getMethod('getAllCategories');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function service_has_clear_commands_cache_method(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        $this->assertTrue($reflection->hasMethod('clearCommandsCache'));
        
        $method = $reflection->getMethod('clearCommandsCache');
        $this->assertTrue($method->isPublic());
        
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function class_structure_is_correct(): void
    {
        $reflection = new \ReflectionClass(ArtisanCommandsService::class);
        
        $this->assertTrue($reflection->isInstantiable());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
