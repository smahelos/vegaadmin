<?php

namespace Tests\Unit\Traits;

use App\Traits\ProductFormFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductFormFieldsTest extends TestCase
{
    #[Test]
    public function trait_exists_and_can_be_used(): void
    {
        $this->assertTrue(trait_exists('App\Traits\ProductFormFields'));
    }

    #[Test]
    public function trait_has_required_methods(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $this->assertContains('getProductFields', $methodNames);
    }

    #[Test]
    public function get_product_fields_method_signature(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $method = $reflection->getMethod('getProductFields');
        $parameters = $method->getParameters();

        $this->assertCount(4, $parameters);
        $this->assertEquals('productCategories', $parameters[0]->getName());
        $this->assertEquals('taxRates', $parameters[1]->getName());
        $this->assertEquals('currencies', $parameters[2]->getName());
        $this->assertEquals('suppliers', $parameters[3]->getName());

        // All parameters should have default values (empty arrays)
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->isDefaultValueAvailable());
            $this->assertEquals([], $parameter->getDefaultValue());
        }
    }

    #[Test]
    public function get_product_fields_method_is_protected(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $method = $reflection->getMethod('getProductFields');

        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function trait_has_correct_namespace(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $this->assertEquals('App\Traits', $reflection->getNamespaceName());
    }

    #[Test]
    public function trait_methods_count(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $methods = $reflection->getMethods();
        
        // Should have exactly 1 method
        $this->assertCount(1, $methods);
    }

    #[Test]
    public function trait_has_no_properties(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $properties = $reflection->getProperties();
        
        // Trait should not define any properties
        $this->assertEmpty($properties);
    }

    #[Test]
    public function trait_is_actually_a_trait(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $this->assertTrue($reflection->isTrait());
    }

    #[Test]
    public function trait_uses_required_models(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $source = file_get_contents($reflection->getFileName());
        
        // Check that trait imports required models
        $this->assertStringContainsString('use App\Models\ProductCategory;', $source);
        $this->assertStringContainsString('use App\Models\Tax;', $source);
        $this->assertStringContainsString('use App\Models\Supplier;', $source);
        $this->assertStringContainsString('use App\Services\CurrencyService;', $source);
        $this->assertStringContainsString('use App\Services\ProductsService;', $source);
    }

    #[Test]
    public function trait_method_has_docblock(): void
    {
        $reflection = new \ReflectionClass('App\Traits\ProductFormFields');
        $method = $reflection->getMethod('getProductFields');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get client form fields definitions', $docComment);
    }
}
