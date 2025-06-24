<?php

namespace Tests\Unit\Services;

use App\Services\TaxesService;
use App\Contracts\TaxesServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxesServiceTest extends TestCase
{
    private TaxesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxesService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(TaxesService::class, $this->service);
        $this->assertInstanceOf(TaxesServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_all_taxes_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllTaxes'));
    }

    #[Test]
    public function get_all_taxes_for_select_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllTaxesForSelect'));
    }

    #[Test]
    public function clear_categories_cache_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'clearCategoriesCache'));
    }

    #[Test]
    public function get_all_taxes_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllTaxes');
        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    #[Test]
    public function get_all_taxes_for_select_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllTaxesForSelect');
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
    public function get_all_taxes_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllTaxes');

        $this->assertTrue($method->isPublic());
    }

    #[Test]
    public function get_all_taxes_for_select_method_is_public(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAllTaxesForSelect');

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
