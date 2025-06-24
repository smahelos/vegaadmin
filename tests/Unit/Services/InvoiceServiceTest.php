<?php

namespace Tests\Unit\Services;

use App\Services\InvoiceService;
use App\Contracts\InvoiceServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceService::class, $this->service);
        $this->assertInstanceOf(InvoiceServiceInterface::class, $this->service);
    }

    #[Test]
    public function get_next_invoice_number_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getNextInvoiceNumber'));
    }

    #[Test]
    public function get_item_units_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getItemUnits'));
    }

    #[Test]
    public function save_invoice_products_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'saveInvoiceProducts'));
    }

    #[Test]
    public function store_temporary_invoice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'storeTemporaryInvoice'));
    }

    #[Test]
    public function get_temporary_invoice_by_token_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'getTemporaryInvoiceByToken'));
    }

    #[Test]
    public function delete_temporary_invoice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteTemporaryInvoice'));
    }

    #[Test]
    public function mark_invoice_as_paid_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'markInvoiceAsPaid'));
    }

    #[Test]
    public function ensure_object_properties_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'ensureObjectProperties'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $expectedMethods = [
            'getNextInvoiceNumber',
            'getItemUnits',
            'saveInvoiceProducts',
            'storeTemporaryInvoice',
            'getTemporaryInvoiceByToken',
            'deleteTemporaryInvoice',
            'markInvoiceAsPaid',
            'ensureObjectProperties'
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
            if ($method->getDeclaringClass()->getName() === InvoiceService::class) {
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
            return $method->getDeclaringClass()->getName() === InvoiceService::class;
        });
        
        $this->assertCount(8, $serviceMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test getNextInvoiceNumber method parameters
        $getNextMethod = $reflection->getMethod('getNextInvoiceNumber');
        $this->assertCount(0, $getNextMethod->getParameters());
        
        // Test getItemUnits method parameters
        $getUnitsMethod = $reflection->getMethod('getItemUnits');
        $this->assertCount(0, $getUnitsMethod->getParameters());
        
        // Test saveInvoiceProducts method parameters
        $saveProductsMethod = $reflection->getMethod('saveInvoiceProducts');
        $saveParams = $saveProductsMethod->getParameters();
        $this->assertCount(2, $saveParams);
        $this->assertEquals('invoice', $saveParams[0]->getName());
        $this->assertEquals('products', $saveParams[1]->getName());
        $this->assertEquals('App\Models\Invoice', $saveParams[0]->getType()->getName());
        $this->assertEquals('array', $saveParams[1]->getType()->getName());
        
        // Test storeTemporaryInvoice method parameters
        $storeMethod = $reflection->getMethod('storeTemporaryInvoice');
        $storeParams = $storeMethod->getParameters();
        $this->assertCount(1, $storeParams);
        $this->assertEquals('data', $storeParams[0]->getName());
        $this->assertEquals('array', $storeParams[0]->getType()->getName());
        
        // Test getTemporaryInvoiceByToken method parameters
        $getTokenMethod = $reflection->getMethod('getTemporaryInvoiceByToken');
        $getTokenParams = $getTokenMethod->getParameters();
        $this->assertCount(1, $getTokenParams);
        $this->assertEquals('token', $getTokenParams[0]->getName());
        $this->assertEquals('string', $getTokenParams[0]->getType()->getName());
        
        // Test deleteTemporaryInvoice method parameters
        $deleteMethod = $reflection->getMethod('deleteTemporaryInvoice');
        $deleteParams = $deleteMethod->getParameters();
        $this->assertCount(1, $deleteParams);
        $this->assertEquals('token', $deleteParams[0]->getName());
        $this->assertEquals('string', $deleteParams[0]->getType()->getName());
        
        // Test markInvoiceAsPaid method parameters
        $markPaidMethod = $reflection->getMethod('markInvoiceAsPaid');
        $markParams = $markPaidMethod->getParameters();
        $this->assertCount(1, $markParams);
        $this->assertEquals('id', $markParams[0]->getName());
        $this->assertEquals('int', $markParams[0]->getType()->getName());
        
        // Test ensureObjectProperties method parameters
        $ensureMethod = $reflection->getMethod('ensureObjectProperties');
        $ensureParams = $ensureMethod->getParameters();
        $this->assertCount(2, $ensureParams);
        $this->assertEquals('object', $ensureParams[0]->getName());
        $this->assertEquals('properties', $ensureParams[1]->getName());
        $this->assertEquals('stdClass', $ensureParams[0]->getType()->getName());
        $this->assertEquals('array', $ensureParams[1]->getType()->getName());
    }

    #[Test]
    public function method_return_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test getNextInvoiceNumber return type
        $getNextMethod = $reflection->getMethod('getNextInvoiceNumber');
        $this->assertEquals('string', $getNextMethod->getReturnType()->getName());
        
        // Test getItemUnits return type
        $getUnitsMethod = $reflection->getMethod('getItemUnits');
        $this->assertEquals('array', $getUnitsMethod->getReturnType()->getName());
        
        // Test saveInvoiceProducts return type
        $saveProductsMethod = $reflection->getMethod('saveInvoiceProducts');
        $this->assertEquals('void', $saveProductsMethod->getReturnType()->getName());
        
        // Test storeTemporaryInvoice return type
        $storeMethod = $reflection->getMethod('storeTemporaryInvoice');
        $this->assertEquals('string', $storeMethod->getReturnType()->getName());
        
        // Test getTemporaryInvoiceByToken return type (nullable array)
        $getTokenMethod = $reflection->getMethod('getTemporaryInvoiceByToken');
        $getTokenReturnType = $getTokenMethod->getReturnType();
        $this->assertNotNull($getTokenReturnType);
        $this->assertTrue($getTokenReturnType->allowsNull());
        $this->assertEquals('array', $getTokenReturnType->getName());
        
        // Test deleteTemporaryInvoice return type
        $deleteMethod = $reflection->getMethod('deleteTemporaryInvoice');
        $this->assertEquals('bool', $deleteMethod->getReturnType()->getName());
        
        // Test markInvoiceAsPaid return type
        $markPaidMethod = $reflection->getMethod('markInvoiceAsPaid');
        $this->assertEquals('bool', $markPaidMethod->getReturnType()->getName());
        
        // Test ensureObjectProperties return type
        $ensureMethod = $reflection->getMethod('ensureObjectProperties');
        $this->assertEquals('void', $ensureMethod->getReturnType()->getName());
    }

    #[Test]
    public function service_has_correct_namespace_and_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('InvoiceService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }

    #[Test]
    public function ensure_object_properties_method_logic(): void
    {
        // Test that the method exists and can handle stdClass objects
        $object = new \stdClass();
        $properties = ['due_in', 'payment_method_id', 'test_property'];
        
        // This should not throw an exception
        $this->service->ensureObjectProperties($object, $properties);
        
        // Verify object properties were set
        $this->assertTrue(property_exists($object, 'due_in'));
        $this->assertTrue(property_exists($object, 'payment_method_id'));
        $this->assertTrue(property_exists($object, 'test_property'));
        $this->assertEquals(14, $object->due_in);
        $this->assertEquals(1, $object->payment_method_id);
        $this->assertEquals('', $object->test_property);
    }
}
