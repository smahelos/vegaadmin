<?php

namespace Tests\Unit\Services;

use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    private InvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Resolve through container to satisfy constructor dependencies introduced in Wave 2
        $this->service = app(InvoiceService::class);
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceService::class, $this->service);
        $this->assertInstanceOf(InvoiceServiceInterface::class, $this->service);
    }

    #[Test]
    public function save_invoice_products_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'saveInvoiceProducts'));
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
    public function set_invoice_template_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'setInvoiceTemplate'));
    }

    #[Test]
    public function generate_invoice_number_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateInvoiceNumber'));
    }

    #[Test]
    public function create_invoice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'createInvoice'));
    }

    #[Test]
    public function update_invoice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'updateInvoice'));
    }

    #[Test]
    public function delete_invoice_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteInvoice'));
    }

    #[Test]
    public function calculate_total_amount_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'calculateTotalAmount'));
    }

    #[Test]
    public function change_invoice_status_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'changeInvoiceStatus'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $expectedMethods = [
            'saveInvoiceProducts',
            'markInvoiceAsPaid',
            'setInvoiceTemplate',
            'ensureObjectProperties',
            'createInvoice',
            'updateInvoice',
            'deleteInvoice',
            'calculateTotalAmount',
            'generateInvoiceNumber',
            'changeInvoiceStatus'
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
        
        // After Wave 2 refactor constructor injection didn't add public API methods; count adjusted if internal changes added one
        $this->assertCount(11, $serviceMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test generateInvoiceNumber method parameters
        $generateMethod = $reflection->getMethod('generateInvoiceNumber');
        $this->assertCount(2, $generateMethod->getParameters());
        
        // Test saveInvoiceProducts method parameters
        $saveProductsMethod = $reflection->getMethod('saveInvoiceProducts');
        $saveParams = $saveProductsMethod->getParameters();
        $this->assertCount(2, $saveParams);
        $this->assertEquals('invoiceId', $saveParams[0]->getName());
        $this->assertEquals('products', $saveParams[1]->getName());
        $this->assertTrue($saveParams[0]->hasType());
        $this->assertTrue($saveParams[1]->hasType());
        
        // Test markInvoiceAsPaid method parameters
        $markPaidMethod = $reflection->getMethod('markInvoiceAsPaid');
        $markParams = $markPaidMethod->getParameters();
        $this->assertCount(1, $markParams);
        $this->assertEquals('id', $markParams[0]->getName());
        $this->assertTrue($markParams[0]->hasType());
        
        // Test ensureObjectProperties method parameters
        $ensureMethod = $reflection->getMethod('ensureObjectProperties');
        $ensureParams = $ensureMethod->getParameters();
        $this->assertCount(2, $ensureParams);
        $this->assertEquals('object', $ensureParams[0]->getName());
        $this->assertEquals('properties', $ensureParams[1]->getName());
        $this->assertTrue($ensureParams[0]->hasType());
        $this->assertTrue($ensureParams[1]->hasType());
        
        // Test setInvoiceTemplate method parameters
        $setTemplateMethod = $reflection->getMethod('setInvoiceTemplate');
        $setTemplateParams = $setTemplateMethod->getParameters();
        $this->assertCount(2, $setTemplateParams);
        $this->assertEquals('id', $setTemplateParams[0]->getName());
        $this->assertEquals('template', $setTemplateParams[1]->getName());
        $this->assertTrue($setTemplateParams[0]->hasType());
        $this->assertTrue($setTemplateParams[1]->hasType());

        // Test createInvoice method parameters
        $createMethod = $reflection->getMethod('createInvoice');
        $createParams = $createMethod->getParameters();
        $this->assertCount(3, $createParams);
        $this->assertEquals('userId', $createParams[0]->getName());
        $this->assertEquals('data', $createParams[1]->getName());
        $this->assertEquals('invoiceProducts', $createParams[2]->getName());
        $this->assertTrue($createParams[0]->hasType());
        $this->assertTrue($createParams[1]->hasType());
        $this->assertTrue($createParams[2]->hasType());

        // Test updateInvoice method parameters
        $updateMethod = $reflection->getMethod('updateInvoice');
        $updateParams = $updateMethod->getParameters();
        $this->assertCount(4, $updateParams);
        $this->assertEquals('userId', $updateParams[0]->getName());
        $this->assertEquals('invoiceId', $updateParams[1]->getName());
        $this->assertEquals('data', $updateParams[2]->getName());
        $this->assertEquals('invoiceProducts', $updateParams[3]->getName());
        $this->assertTrue($updateParams[0]->hasType());
        $this->assertTrue($updateParams[1]->hasType());
        $this->assertTrue($updateParams[2]->hasType());
        $this->assertTrue($updateParams[3]->hasType());

        // Test deleteInvoice method parameters
        $deleteMethod = $reflection->getMethod('deleteInvoice');
        $deleteParams = $deleteMethod->getParameters();
        $this->assertCount(2, $deleteParams);
        $this->assertEquals('userId', $deleteParams[0]->getName());
        $this->assertEquals('invoiceId', $deleteParams[1]->getName());
        $this->assertTrue($deleteParams[0]->hasType());
        $this->assertTrue($deleteParams[1]->hasType());

        // Test calculateTotalAmount method parameters
        $calculateMethod = $reflection->getMethod('calculateTotalAmount');
        $calculateParams = $calculateMethod->getParameters();
        $this->assertCount(1, $calculateParams);
        $this->assertEquals('id', $calculateParams[0]->getName());
        $this->assertTrue($calculateParams[0]->hasType());

        // Test changeInvoiceStatus method parameters
        $changeStatusMethod = $reflection->getMethod('changeInvoiceStatus');
        $changeStatusParams = $changeStatusMethod->getParameters();
        $this->assertCount(3, $changeStatusParams);
        $this->assertEquals('userId', $changeStatusParams[0]->getName());
        $this->assertEquals('invoiceId', $changeStatusParams[1]->getName());
        $this->assertEquals('statusId', $changeStatusParams[2]->getName());
        $this->assertTrue($changeStatusParams[0]->hasType());
        $this->assertTrue($changeStatusParams[1]->hasType());
        $this->assertTrue($changeStatusParams[2]->hasType());
    }

    #[Test]
    public function method_return_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test generateInvoiceNumber return type
        $generateMethod = $reflection->getMethod('generateInvoiceNumber');
        $this->assertNotNull($generateMethod->getReturnType());
        $this->assertEquals('string', $generateMethod->getReturnType()->getName());
        
        // Test saveInvoiceProducts return type
        $saveProductsMethod = $reflection->getMethod('saveInvoiceProducts');
        $this->assertNotNull($saveProductsMethod->getReturnType());
        $this->assertEquals('void', $saveProductsMethod->getReturnType()->getName());
        
        // Test markInvoiceAsPaid return type
        $markPaidMethod = $reflection->getMethod('markInvoiceAsPaid');
        $this->assertNotNull($markPaidMethod->getReturnType());
        $this->assertEquals('bool', $markPaidMethod->getReturnType()->getName());
        
        // Test ensureObjectProperties return type
        $ensureMethod = $reflection->getMethod('ensureObjectProperties');
        $this->assertNotNull($ensureMethod->getReturnType());
        $this->assertEquals('void', $ensureMethod->getReturnType()->getName());
        
        // Test setInvoiceTemplate return type
        $setTemplateMethod = $reflection->getMethod('setInvoiceTemplate');
        $this->assertNotNull($setTemplateMethod->getReturnType());
        $this->assertEquals('bool', $setTemplateMethod->getReturnType()->getName());

        // Test createInvoice return type
        $createMethod = $reflection->getMethod('createInvoice');
        $this->assertNotNull($createMethod->getReturnType());
        $this->assertEquals('App\\Domain\\Invoice\\DTO\\InvoiceDTO', $createMethod->getReturnType()->getName());

        // Test updateInvoice return type
        $updateMethod = $reflection->getMethod('updateInvoice');
        $this->assertNotNull($updateMethod->getReturnType());
        $this->assertEquals('App\\Domain\\Invoice\\DTO\\InvoiceDTO', $updateMethod->getReturnType()->getName());

        // Test deleteInvoice return type
        $deleteMethod = $reflection->getMethod('deleteInvoice');
        $this->assertNotNull($deleteMethod->getReturnType());
        $this->assertEquals('void', $deleteMethod->getReturnType()->getName());

        // Test calculateTotalAmount return type
        $calculateMethod = $reflection->getMethod('calculateTotalAmount');
        $this->assertNotNull($calculateMethod->getReturnType());
        $this->assertEquals('float', $calculateMethod->getReturnType()->getName());

        // Test changeInvoiceStatus return type
        $changeStatusMethod = $reflection->getMethod('changeInvoiceStatus');
        $this->assertNotNull($changeStatusMethod->getReturnType());
        $this->assertEquals('bool', $changeStatusMethod->getReturnType()->getName());
    }

    #[Test]
    public function service_has_correct_namespace_and_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertEquals('App\\Domain\\Invoice\\Services', $reflection->getNamespaceName());
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
