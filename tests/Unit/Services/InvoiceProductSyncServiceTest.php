<?php

namespace Tests\Unit\Services;

use App\Services\InvoiceProductSyncService;
use App\Contracts\InvoiceProductSyncServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceProductSyncServiceTest extends TestCase
{
    private InvoiceProductSyncService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceProductSyncService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoiceProductSyncService::class, $this->service);
        $this->assertInstanceOf(InvoiceProductSyncServiceInterface::class, $this->service);
    }

    #[Test]
    public function sync_products_from_json_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'syncProductsFromJson'));
    }

    #[Test]
    public function sync_all_invoices_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'syncAllInvoices'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $expectedMethods = ['syncProductsFromJson', 'syncAllInvoices'];
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
            if ($method->getDeclaringClass()->getName() === InvoiceProductSyncService::class) {
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
            return $method->getDeclaringClass()->getName() === InvoiceProductSyncService::class;
        });
        
        $this->assertCount(2, $serviceMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test syncProductsFromJson method parameters
        $syncMethod = $reflection->getMethod('syncProductsFromJson');
        $parameters = $syncMethod->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('App\Models\Invoice', $parameters[0]->getType()->getName());
        
        // Test syncAllInvoices method parameters
        $syncAllMethod = $reflection->getMethod('syncAllInvoices');
        $allParameters = $syncAllMethod->getParameters();
        $this->assertCount(0, $allParameters);
    }

    #[Test]
    public function method_return_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test syncProductsFromJson return type
        $syncMethod = $reflection->getMethod('syncProductsFromJson');
        $returnType = $syncMethod->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('void', $returnType->getName());
        
        // Test syncAllInvoices return type
        $syncAllMethod = $reflection->getMethod('syncAllInvoices');
        $allReturnType = $syncAllMethod->getReturnType();
        $this->assertNotNull($allReturnType);
        $this->assertEquals('void', $allReturnType->getName());
    }

    #[Test]
    public function service_has_correct_namespace_and_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('InvoiceProductSyncService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }
}
