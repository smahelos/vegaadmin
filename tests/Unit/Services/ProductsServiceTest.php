<?php

namespace Tests\Unit\Services;

use App\Services\ProductsService;
use App\Contracts\ProductsServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductsServiceTest extends TestCase
{
    private ProductsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductsService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ProductsService::class, $this->service);
        $this->assertInstanceOf(ProductsServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_all_categories_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllCategories'));
    }

    #[Test]
    public function get_all_suppliers_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getAllSuppliers'));
    }

    #[Test]
    public function clear_categories_cache_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'clearCategoriesCache'));
    }

    #[Test]
    public function handle_product_image_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'handleProductImage'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $expectedMethods = [
            'getAllCategories',
            'getAllSuppliers',
            'clearCategoriesCache',
            'handleProductImage'
        ];
        $actualMethods = get_class_methods($this->service);
        
        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $actualMethods);
        }
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $methodsWithoutReturnType = [];
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === ProductsService::class) {
                if ($method->getReturnType() === null && $method->getName() !== '__construct') {
                    $methodsWithoutReturnType[] = $method->getName();
                }
            }
        }
        
        $this->assertEmpty($methodsWithoutReturnType, 
            'Methods without return types: ' . implode(', ', $methodsWithoutReturnType));
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        $serviceMethods = array_filter($publicMethods, function($method) {
            return $method->getDeclaringClass()->getName() === ProductsService::class;
        });
        
        $this->assertCount(4, $serviceMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test getAllCategories method parameters
        $getCategoriesMethod = $reflection->getMethod('getAllCategories');
        $this->assertCount(0, $getCategoriesMethod->getParameters());
        
        // Test getAllSuppliers method parameters
        $getSuppliersMethod = $reflection->getMethod('getAllSuppliers');
        $this->assertCount(0, $getSuppliersMethod->getParameters());
        
        // Test clearCategoriesCache method parameters
        $clearCacheMethod = $reflection->getMethod('clearCategoriesCache');
        $this->assertCount(0, $clearCacheMethod->getParameters());
        
        // Test handleProductImage method parameters
        $handleImageMethod = $reflection->getMethod('handleProductImage');
        $handleParams = $handleImageMethod->getParameters();
        $this->assertCount(2, $handleParams);
        $this->assertEquals('image', $handleParams[0]->getName());
        $this->assertEquals('oldImage', $handleParams[1]->getName());
        $this->assertTrue($handleParams[0]->allowsNull());
        $this->assertTrue($handleParams[1]->allowsNull());
    }

    #[Test]
    public function method_return_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test getAllCategories return type
        $getCategoriesMethod = $reflection->getMethod('getAllCategories');
        $this->assertEquals('array', $getCategoriesMethod->getReturnType()->getName());
        
        // Test getAllSuppliers return type
        $getSuppliersMethod = $reflection->getMethod('getAllSuppliers');
        $this->assertEquals('array', $getSuppliersMethod->getReturnType()->getName());
        
        // Test clearCategoriesCache return type
        $clearCacheMethod = $reflection->getMethod('clearCategoriesCache');
        $this->assertEquals('void', $clearCacheMethod->getReturnType()->getName());
        
        // Test handleProductImage return type
        $handleImageMethod = $reflection->getMethod('handleProductImage');
        $handleReturnType = $handleImageMethod->getReturnType();
        $this->assertNotNull($handleReturnType);
        $this->assertTrue($handleReturnType->allowsNull());
        $this->assertEquals('string', $handleReturnType->getName());
    }

    #[Test]
    public function service_has_correct_namespace_and_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('ProductsService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
