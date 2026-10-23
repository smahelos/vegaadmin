<?php

namespace Tests\Unit\Traits;

use App\Infrastructure\Forms\Products\ProductFormFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductFormFieldsTest extends TestCase
{
    use ProductFormFields;

    #[Test]
    public function trait_can_be_used(): void
    {
        $this->assertTrue(method_exists($this, 'getProductFields'));
    }

    #[Test]
    public function get_product_fields_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'getProductFields'));
    }

    #[Test]
    public function get_product_fields_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
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
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getProductFields');

        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function trait_methods_count(): void
    {
        $reflection = new \ReflectionClass(ProductFormFields::class);
        $methods = $reflection->getMethods();

        // Should have exactly 1 method
        $this->assertCount(1, $methods);
    }

    #[Test]
    public function trait_has_no_properties(): void
    {
        $reflection = new \ReflectionClass(ProductFormFields::class);
        $properties = $reflection->getProperties();

        // Trait should not define any properties
        $this->assertEmpty($properties);
    }

    #[Test]
    public function trait_method_has_docblock(): void
    {
        $reflection = new \ReflectionClass(ProductFormFields::class);
        $method = $reflection->getMethod('getProductFields');
        $docComment = $method->getDocComment();

        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get client form fields definitions', $docComment);
    }
}
