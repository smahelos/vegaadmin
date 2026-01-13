<?php

namespace Tests\Unit\Domain\Invoice\Traits;

use App\Infrastructure\Forms\Invoice\InvoiceFormFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceFormFieldsTest extends TestCase
{
    use InvoiceFormFields;

    #[Test]
    public function trait_can_be_used(): void
    {
        $this->assertTrue(method_exists($this, 'getInvoiceFields'));
    }

    #[Test]
    public function get_invoice_fields_method_exists(): void
    {
        $this->assertTrue(method_exists($this, 'getInvoiceFields'));
    }

    #[Test]
    public function get_invoice_fields_method_signature(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getInvoiceFields');
        $parameters = $method->getParameters();

        $this->assertCount(5, $parameters);
        $this->assertEquals('clients', $parameters[0]->getName());
        $this->assertEquals('suppliers', $parameters[1]->getName());
        $this->assertEquals('paymentMethods', $parameters[2]->getName());
        $this->assertEquals('statuses', $parameters[3]->getName());
        $this->assertEquals('currencies', $parameters[4]->getName());

        // All parameters should have default values (empty arrays)
        foreach ($parameters as $parameter) {
            $this->assertTrue($parameter->isDefaultValueAvailable());
            $this->assertEquals([], $parameter->getDefaultValue());
        }
    }

    #[Test]
    public function get_invoice_fields_method_is_protected(): void
    {
        $reflection = new \ReflectionClass($this);
        $method = $reflection->getMethod('getInvoiceFields');

        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function trait_methods_count(): void
    {
        $reflection = new \ReflectionClass(InvoiceFormFields::class);
        $methods = $reflection->getMethods();
        
        // Should have exactly 1 method
        $this->assertCount(1, $methods);
    }

    #[Test]
    public function trait_has_no_properties(): void
    {
        $reflection = new \ReflectionClass(InvoiceFormFields::class);
        $properties = $reflection->getProperties();
        
        // Trait should not define any properties
        $this->assertEmpty($properties);
    }

    #[Test]
    public function trait_uses_required_models(): void
    {
        $reflection = new \ReflectionClass(InvoiceFormFields::class);
        $source = file_get_contents($reflection->getFileName());
        
        // Check that trait imports required models
        $this->assertStringContainsString('use App\Models\Client;', $source);
        $this->assertStringContainsString('use App\Models\Supplier;', $source);
        $this->assertStringContainsString('use App\Models\PaymentMethod;', $source);
        $this->assertStringContainsString('use App\Models\Status;', $source);
    $this->assertStringContainsString('use App\\Domain\\Shared\\Geography\\Contracts\\CountryServiceInterface;', $source);
    }

    #[Test]
    public function trait_method_has_docblock(): void
    {
        $reflection = new \ReflectionClass(InvoiceFormFields::class);
        $method = $reflection->getMethod('getInvoiceFields');
        $docComment = $method->getDocComment();
        
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('Get invoice form field definitions', $docComment);
    }
}
