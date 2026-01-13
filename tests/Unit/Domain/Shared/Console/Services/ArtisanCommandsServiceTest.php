<?php

namespace Tests\Unit\Domain\Shared\Console\Services;

use App\Domain\Shared\Console\Services\ArtisanCommandsService;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsServiceInterface;
use App\Domain\Shared\Console\Contracts\ArtisanCommandsDtoRepositoryInterface;
use App\Domain\Shared\Cache\Contracts\CacheServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Domain-aligned unit tests for ArtisanCommandsService focusing on method signatures & structure.
 */
class ArtisanCommandsServiceTest extends TestCase
{
    private ArtisanCommandsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ArtisanCommandsService(
            $this->createMock(ArtisanCommandsDtoRepositoryInterface::class),
            $this->createMock(CacheServiceInterface::class)
        );
    }

    #[Test]
    public function service_and_interface(): void
    {
        $this->assertInstanceOf(ArtisanCommandsService::class, $this->service);
        $this->assertInstanceOf(ArtisanCommandsServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_all_commands_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->hasMethod('getAllCommands'));
        $m = $r->getMethod('getAllCommands');
        $this->assertTrue($m->isPublic());
        $params = $m->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('onlyNames', $params[0]->getName());
        $this->assertEquals('bool', (string)$params[0]->getType());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertFalse($params[0]->getDefaultValue());
        $this->assertEquals('array', (string)$m->getReturnType());
    }

    #[Test]
    public function get_commands_by_category_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->hasMethod('getCommandsByCategory'));
        $m = $r->getMethod('getCommandsByCategory');
        $this->assertTrue($m->isPublic());
        $params = $m->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('categorySlug', $params[0]->getName());
        $this->assertEquals('?string', (string)$params[0]->getType());
        $this->assertTrue($params[0]->isDefaultValueAvailable());
        $this->assertNull($params[0]->getDefaultValue());
        $this->assertEquals('withoutCategory', $params[1]->getName());
        $this->assertEquals('bool', (string)$params[1]->getType());
        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertFalse($params[1]->getDefaultValue());
        $this->assertEquals('array', (string)$m->getReturnType());
    }

    #[Test]
    public function get_all_categories_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->hasMethod('getAllCategories'));
        $m = $r->getMethod('getAllCategories');
        $this->assertTrue($m->isPublic());
        $this->assertEquals('array', (string)$m->getReturnType());
    }

    #[Test]
    public function clear_commands_cache_signature(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->hasMethod('clearCommandsCache'));
        $m = $r->getMethod('clearCommandsCache');
        $this->assertTrue($m->isPublic());
        $this->assertEquals('void', (string)$m->getReturnType());
    }

    #[Test]
    public function class_structure(): void
    {
        $r = new \ReflectionClass($this->service);
        $this->assertTrue($r->isInstantiable());
        $this->assertEquals('App\\Domain\\Shared\\Console\\Services', $r->getNamespaceName());
    }
}
