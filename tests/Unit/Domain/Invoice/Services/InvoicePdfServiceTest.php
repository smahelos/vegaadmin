<?php

namespace Tests\Unit\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoicePdfServiceInterface;
use App\Application\Invoice\Services\InvoicePdfApplicationService;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\Services\InvoicePdfService;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\User\Services\LocaleService;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Domain\User\Contracts\Locale;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class InvoicePdfServiceTest extends TestCase
{
    private InvoicePdfApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $locale = $this->createMock(LocaleService::class);
        $qr = $this->createMock(QrPaymentServiceInterface::class);
        $invoice = $this->createMock(InvoiceServiceInterface::class);
        $money = $this->createMock(MoneyFormatterInterface::class);
        $this->service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $this->createMock(InvoiceDtoReadRepositoryInterface::class),
            $this->createMock(InvoicePrintDataBuilderInterface::class),
            $this->createMock(InvoicePdfRendererInterface::class),
            $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class),
            $this->createMock(Locale::class),
            $this->createMock(TemporaryInvoiceStoreInterface::class),
        );
    }

    #[Test]
    public function service_and_interface(): void
    {   $this->assertInstanceOf(InvoicePdfApplicationService::class,$this->service); $this->assertInstanceOf(InvoicePdfApplicationServiceInterface::class,$this->service); }

    #[Test]
    public function constructor_parameters(): void
    {   $r=new ReflectionClass($this->service); $ctor=$r->getConstructor(); $this->assertNotNull($ctor); $this->assertCount(7,$ctor->getParameters()); }

    #[Test]
    public function generate_pdf_signature(): void
    {   $r=new ReflectionClass($this->service); $m=$r->getMethod('generateForUser'); $this->assertCount(4,$m->getParameters()); $this->assertEquals('int',(string)$m->getParameters()[0]->getType()); $this->assertEquals('?string',(string)$m->getParameters()[2]->getType()); }

    #[Test]
    public function generate_pdf_from_data_signature(): void
    {   $r=new ReflectionClass($this->service); $m=$r->getMethod('generateForGuestByToken'); $this->assertCount(3,$m->getParameters()); $this->assertEquals('?string',(string)$m->getParameters()[0]->getType()); $this->assertEquals('?string',(string)$m->getParameters()[1]->getType()); }

    #[Test]
    public function public_methods_exist(): void
    {   $r=new ReflectionClass($this->service); foreach(['generateForUser','generateForGuestByToken','generate'] as $m){ $this->assertTrue($r->hasMethod($m)); $this->assertTrue($r->getMethod($m)->isPublic()); } }

    #[Test]
    public function public_methods_counts(): void
    {   $r=new ReflectionClass($this->service); $pub=array_filter($r->getMethods(\ReflectionMethod::IS_PUBLIC),fn($m)=>$m->getDeclaringClass()->getName()===InvoicePdfApplicationService::class && $m->getName()!=='__construct'); $this->assertCount(3,$pub); }
}
