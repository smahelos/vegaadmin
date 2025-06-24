<?php

namespace Tests\Unit\Services;

use App\Contracts\ProductServiceInterface;
use App\Contracts\ProductRepositoryInterface;
use App\Contracts\CacheServiceInterface;
use App\Services\ProductService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the repository and cache service dependencies
        $mockRepository = $this->createMock(ProductRepositoryInterface::class);
        $mockCacheService = $this->createMock(CacheServiceInterface::class);
        $this->service = new ProductService($mockRepository, $mockCacheService);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ProductService::class, $this->service);
    }

    #[Test]
    public function service_implements_product_service_interface(): void
    {
        $this->assertInstanceOf(ProductServiceInterface::class, $this->service);
    }

    #[Test]
    public function create_product_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'createProduct'));
    }

    #[Test]
    public function update_product_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateProduct'));
    }

    #[Test]
    public function delete_product_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteProduct'));
    }

    #[Test]
    public function get_form_data_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getFormData'));
    }

    #[Test]
    public function generate_slug_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateSlug'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasMethod('createProduct'));
        $this->assertTrue($reflection->hasMethod('updateProduct'));
        $this->assertTrue($reflection->hasMethod('deleteProduct'));
        $this->assertTrue($reflection->hasMethod('getFormData'));
        $this->assertTrue($reflection->hasMethod('generateSlug'));
    }

    #[Test]
    public function all_public_methods_have_return_types(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() === ProductService::class && $method->getName() !== '__construct') {
                $this->assertNotNull(
                    $method->getReturnType(),
                    "Method {$method->getName()} should have a return type"
                );
            }
        }
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new \ReflectionClass($this->service);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($method) => $method->getDeclaringClass()->getName() === ProductService::class && $method->getName() !== '__construct'
        );
        
        $this->assertCount(6, $publicMethods, 'ProductService should have exactly 6 public methods (excluding constructor)');
    }

    #[Test]
    public function generate_slug_returns_string(): void
    {
        $slug = $this->service->generateSlug('Test Product Name');
        
        $this->assertIsString($slug);
        $this->assertEquals('test-product-name', $slug);
    }

    #[Test]
    public function generate_slug_handles_special_characters(): void
    {
        $slug = $this->service->generateSlug('Test Product! @#$%^&*()');
        
        $this->assertIsString($slug);
        $this->assertStringNotContainsString('!', $slug);
        $this->assertStringNotContainsString('@', $slug);
    }
}
