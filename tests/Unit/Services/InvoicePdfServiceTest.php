<?php

namespace Tests\Unit\Services;

use App\Services\InvoicePdfService;
use App\Services\LocaleService;
use App\Services\QrPaymentService;
use App\Services\InvoiceService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class InvoicePdfServiceTest extends TestCase
{
    private InvoicePdfService $service;
    private LocaleService $localeService;
    private QrPaymentService $qrPaymentService;
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock dependencies
        $this->localeService = $this->createMock(LocaleService::class);
        $this->qrPaymentService = $this->createMock(QrPaymentService::class);
        $this->invoiceService = $this->createMock(InvoiceService::class);
        
        $this->service = new InvoicePdfService(
            $this->localeService,
            $this->qrPaymentService,
            $this->invoiceService
        );
    }

    #[Test]
    public function service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(InvoicePdfService::class, $this->service);
    }

    #[Test]
    public function service_has_required_dependencies(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        $this->assertTrue($reflection->hasProperty('localeService'));
        $this->assertTrue($reflection->hasProperty('qrPaymentService'));
        $this->assertTrue($reflection->hasProperty('invoiceService'));
        
        $localeServiceProperty = $reflection->getProperty('localeService');
        $this->assertTrue($localeServiceProperty->isProtected());
        
        $qrPaymentServiceProperty = $reflection->getProperty('qrPaymentService');
        $this->assertTrue($qrPaymentServiceProperty->isProtected());
        
        $invoiceServiceProperty = $reflection->getProperty('invoiceService');
        $this->assertTrue($invoiceServiceProperty->isProtected());
    }

    #[Test]
    public function constructor_accepts_correct_parameters(): void
    {
        $reflection = new ReflectionClass($this->service);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        
        $parameters = $constructor->getParameters();
        $this->assertCount(3, $parameters);
        
        $this->assertEquals('localeService', $parameters[0]->getName());
        $this->assertEquals('qrPaymentService', $parameters[1]->getName());
        $this->assertEquals('invoiceService', $parameters[2]->getName());
        
        $this->assertEquals('App\Services\LocaleService', $parameters[0]->getType()->getName());
        $this->assertEquals('App\Services\QrPaymentService', $parameters[1]->getType()->getName());
        $this->assertEquals('App\Services\InvoiceService', $parameters[2]->getType()->getName());
    }

    #[Test]
    public function generate_pdf_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generatePdf'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generatePdf');
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('invoice', $parameters[0]->getName());
        $this->assertEquals('requestLocale', $parameters[1]->getName());
        
        $this->assertEquals('App\Models\Invoice', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function generate_pdf_from_data_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generatePdfFromData'));
        
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('generatePdfFromData');
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters);
        $this->assertEquals('invoiceData', $parameters[0]->getName());
        $this->assertEquals('requestLocale', $parameters[1]->getName());
        
        $this->assertEquals('array', $parameters[0]->getType()->getName());
        $this->assertTrue($parameters[1]->getType()->allowsNull());
        $this->assertEquals('string', $parameters[1]->getType()->getName());
    }

    #[Test]
    public function private_methods_exist(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check private methods
        $this->assertTrue($reflection->hasMethod('processInvoiceProductsFromData'));
        $this->assertTrue($reflection->hasMethod('getPaymentMethodFromData'));
        $this->assertTrue($reflection->hasMethod('getPaymentStatusFromData'));
        $this->assertTrue($reflection->hasMethod('generateQrCodeFromInvoiceObject'));
        
        $processInvoiceProductsMethod = $reflection->getMethod('processInvoiceProductsFromData');
        $this->assertTrue($processInvoiceProductsMethod->isPrivate());
        
        $getPaymentMethodMethod = $reflection->getMethod('getPaymentMethodFromData');
        $this->assertTrue($getPaymentMethodMethod->isPrivate());
        
        $getPaymentStatusMethod = $reflection->getMethod('getPaymentStatusFromData');
        $this->assertTrue($getPaymentStatusMethod->isPrivate());
        
        $generateQrCodeMethod = $reflection->getMethod('generateQrCodeFromInvoiceObject');
        $this->assertTrue($generateQrCodeMethod->isPrivate());
    }

    #[Test]
    public function process_invoice_products_from_data_method_signature(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('processInvoiceProductsFromData');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('invoiceData', $parameters[0]->getName());
        $this->assertEquals('array', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function get_payment_method_from_data_method_signature(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getPaymentMethodFromData');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('stdClass', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('tempInvoice', $parameters[0]->getName());
        $this->assertEquals('stdClass', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function get_payment_status_from_data_method_signature(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('getPaymentStatusFromData');
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('stdClass', $returnType->getName());
        
        // Check parameters
        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('tempInvoice', $parameters[0]->getName());
        $this->assertEquals('stdClass', $parameters[0]->getType()->getName());
    }

    #[Test]
    public function service_has_correct_structure(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        // Check that class is properly structured
        $this->assertEquals('App\Services', $reflection->getNamespaceName());
        $this->assertEquals('InvoicePdfService', $reflection->getShortName());
        $this->assertFalse($reflection->isAbstract());
        $this->assertFalse($reflection->isInterface());
    }

    #[Test]
    public function public_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $publicMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        // Filter out inherited methods and constructor
        $customPublicMethods = array_filter($publicMethods, function ($method) {
            return $method->getDeclaringClass()->getName() === InvoicePdfService::class
                && $method->getName() !== '__construct';
        });
        
        $this->assertCount(2, $customPublicMethods);
    }

    #[Test]
    public function private_methods_count(): void
    {
        $reflection = new ReflectionClass($this->service);
        $privateMethods = $reflection->getMethods(\ReflectionMethod::IS_PRIVATE);
        
        // Filter to only methods declared in this class
        $customPrivateMethods = array_filter($privateMethods, function ($method) {
            return $method->getDeclaringClass()->getName() === InvoicePdfService::class;
        });
        
        $this->assertCount(4, $customPrivateMethods);
    }

    #[Test]
    public function dependencies_are_injected_correctly(): void
    {
        $reflection = new ReflectionClass($this->service);
        
        $localeServiceProperty = $reflection->getProperty('localeService');
        $localeServiceProperty->setAccessible(true);
        $this->assertInstanceOf(LocaleService::class, $localeServiceProperty->getValue($this->service));
        
        $qrPaymentServiceProperty = $reflection->getProperty('qrPaymentService');
        $qrPaymentServiceProperty->setAccessible(true);
        $this->assertInstanceOf(QrPaymentService::class, $qrPaymentServiceProperty->getValue($this->service));
        
        $invoiceServiceProperty = $reflection->getProperty('invoiceService');
        $invoiceServiceProperty->setAccessible(true);
        $this->assertInstanceOf(InvoiceService::class, $invoiceServiceProperty->getValue($this->service));
    }
}
