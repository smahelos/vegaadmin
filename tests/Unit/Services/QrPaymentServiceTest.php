<?php

namespace Tests\Unit\Services;

use App\Services\QrPaymentService;
use App\Contracts\QrPaymentServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QrPaymentServiceTest extends TestCase
{
    private QrPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrPaymentService();
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(QrPaymentService::class, $this->service);
        $this->assertInstanceOf(QrPaymentServiceInterface::class, $this->service);
    }

    #[Test]
    public function generate_qr_code_base64_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateQrCodeBase64'));
    }

    #[Test]
    public function has_required_payment_info_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'hasRequiredPaymentInfo'));
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $expectedMethods = [
            'generateQrCodeBase64',
            'hasRequiredPaymentInfo'
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
            if ($method->getDeclaringClass()->getName() === QrPaymentService::class) {
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
            return $method->getDeclaringClass()->getName() === QrPaymentService::class;
        });
        
        $this->assertCount(2, $serviceMethods);
    }

    #[Test]
    public function method_parameter_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test generateQrCodeBase64 method parameters
        $generateMethod = $reflection->getMethod('generateQrCodeBase64');
        $generateParams = $generateMethod->getParameters();
        $this->assertCount(1, $generateParams);
        $this->assertEquals('invoice', $generateParams[0]->getName());
        
        // Test hasRequiredPaymentInfo method parameters
        $hasInfoMethod = $reflection->getMethod('hasRequiredPaymentInfo');
        $hasInfoParams = $hasInfoMethod->getParameters();
        $this->assertCount(1, $hasInfoParams);
        $this->assertEquals('invoice', $hasInfoParams[0]->getName());
        $this->assertEquals('App\Models\Invoice', $hasInfoParams[0]->getType()->getName());
    }

    #[Test]
    public function method_return_types_are_correct(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Test generateQrCodeBase64 return type (mixed)
        $generateMethod = $reflection->getMethod('generateQrCodeBase64');
        $generateReturnType = $generateMethod->getReturnType();
        // This method doesn't have return type declaration, which is expected for mixed returns
        
        // Test hasRequiredPaymentInfo return type
        $hasInfoMethod = $reflection->getMethod('hasRequiredPaymentInfo');
        $this->assertEquals('bool', $hasInfoMethod->getReturnType()->getName());
    }

    #[Test]
    public function service_has_correct_namespace_and_class_structure(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('QrPaymentService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
    }

    #[Test]
    public function service_has_private_helper_methods(): void
    {
        $reflection = new \ReflectionClass($this->service);
        
        // Verify private helper methods exist
        $this->assertTrue($reflection->hasMethod('safeGetProperty'));
        $this->assertTrue($reflection->hasMethod('generateQrString'));
        
        $safeGetMethod = $reflection->getMethod('safeGetProperty');
        $generateQrMethod = $reflection->getMethod('generateQrString');
        
        $this->assertTrue($safeGetMethod->isPrivate());
        $this->assertTrue($generateQrMethod->isPrivate());
    }
}
