<?php

namespace Tests\Unit\Services;

use App\Services\StatusService;
use App\Contracts\StatusServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatusServiceTest extends TestCase
{
    private StatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StatusService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(StatusService::class, $this->service);
        $this->assertInstanceOf(StatusServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_all_categories_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCategories'));
    }

    #[Test]
    public function clear_categories_cache_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'clearCategoriesCache'));
    }

    #[Test]
    public function get_all_categories_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCategories');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function clear_categories_cache_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('clearCategoriesCache');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
    }

    #[Test]
    public function get_all_categories_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllCategories');

        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function clear_categories_cache_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('clearCategoriesCache');

        $this->assertTrue($method->isPublic());
    }
}
