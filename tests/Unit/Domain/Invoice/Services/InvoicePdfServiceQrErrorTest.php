<?php

namespace Tests\Unit\Domain\Invoice\Services;

use App\Application\Invoice\Services\InvoicePdfApplicationService;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Domain\User\Contracts\Locale;

/**
 * Unit test focusing on error handling in InvoicePdfApplicationService.
 * Tests that the service gracefully handles various failure scenarios.
 */
class InvoicePdfServiceQrErrorTest extends TestCase
{
    #[Test]
    public function generateForUser_handles_model_not_found_exception(): void
    {
        // Mock repository that throws ModelNotFoundException
        $invoiceDtoReadRepository = $this->createMock(InvoiceDtoReadRepositoryInterface::class);
        $invoiceDtoReadRepository->method('findByIdForUser')
            ->willReturn(null); // This will trigger ModelNotFoundException

        $service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $invoiceDtoReadRepository,
            $this->createMock(InvoicePrintDataBuilderInterface::class),
            $this->createMock(InvoicePdfRendererInterface::class),
            $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class),
            $this->createMock(Locale::class),
            $this->createMock(TemporaryInvoiceStoreInterface::class),
        );

        $result = $service->generateForUser(1, 123, 'cs', false);

        $this->assertInstanceOf(InvoiceActionResult::class, $result);
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status);
        $this->assertEquals('invoices.messages.show_error', $result->message);
        $this->assertNull($result->response);
    }

    #[Test]
    public function generateForUser_handles_general_exception(): void
    {
        // Mock repository that returns DTO but other service throws exception
        $invoiceDto = $this->createMock(\App\Domain\Invoice\DTO\InvoiceDTO::class);
        
        $invoiceDtoReadRepository = $this->createMock(InvoiceDtoReadRepositoryInterface::class);
        $invoiceDtoReadRepository->method('findByIdForUser')
            ->willReturn($invoiceDto);

        // Mock printDataBuilder that throws exception (simulating QR or other error)
        $printDataBuilder = $this->createMock(InvoicePrintDataBuilderInterface::class);
        $printDataBuilder->method('build')
            ->willThrowException(new \RuntimeException('QR generation failed'));

        $service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $invoiceDtoReadRepository,
            $printDataBuilder,
            $this->createMock(InvoicePdfRendererInterface::class),
            $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class),
            $this->createMock(Locale::class),
            $this->createMock(TemporaryInvoiceStoreInterface::class),
        );

        $result = $service->generateForUser(1, 123, 'cs', false);

        $this->assertInstanceOf(InvoiceActionResult::class, $result);
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status);
        $this->assertEquals('invoices.messages.pdf_error', $result->message);
        $this->assertNull($result->response);
    }

    #[Test]
    public function generateForGuestByToken_handles_missing_token(): void
    {
        $service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $this->createMock(InvoiceDtoReadRepositoryInterface::class),
            $this->createMock(InvoicePrintDataBuilderInterface::class),
            $this->createMock(InvoicePdfRendererInterface::class),
            $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class),
            $this->createMock(Locale::class),
            $this->createMock(TemporaryInvoiceStoreInterface::class),
        );

        $result = $service->generateForGuestByToken(null, 'cs', false);

        $this->assertInstanceOf(InvoiceActionResult::class, $result);
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status);
        $this->assertEquals('invoices.messages.not_found', $result->message);
        $this->assertNull($result->response);
    }

    #[Test]
    public function generateForGuestByToken_handles_expired_token(): void
    {
        // Mock store that returns null (expired/missing token)
        $temporaryInvoiceStore = $this->createMock(TemporaryInvoiceStoreInterface::class);
        $temporaryInvoiceStore->method('get')
            ->willReturn(null);

        $service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $this->createMock(InvoiceDtoReadRepositoryInterface::class),
            $this->createMock(InvoicePrintDataBuilderInterface::class),
            $this->createMock(InvoicePdfRendererInterface::class),
            $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class),
            $this->createMock(Locale::class),
            $temporaryInvoiceStore,
        );

        $result = $service->generateForGuestByToken('test-token', 'cs', false);

        $this->assertInstanceOf(InvoiceActionResult::class, $result);
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status);
        $this->assertEquals('invoices.messages.expired', $result->message);
        $this->assertNull($result->response);
    }

    #[Test]
    public function generateForGuestByToken_handles_general_exception(): void
    {
        // Mock store that returns data but factory throws exception
        $temporaryInvoiceStore = $this->createMock(TemporaryInvoiceStoreInterface::class);
        $temporaryInvoiceStore->method('get')
            ->willReturn(['some' => 'data']);

        $tempPrintDataFactory = $this->createMock(TemporaryInvoicePrintDataFactoryInterface::class);
        $tempPrintDataFactory->method('fromArray')
            ->willThrowException(new \RuntimeException('Print data creation failed'));

        $service = new InvoicePdfApplicationService(
            $this->createMock(InvoiceServiceInterface::class),
            $this->createMock(InvoiceDtoReadRepositoryInterface::class),
            $this->createMock(InvoicePrintDataBuilderInterface::class),
            $this->createMock(InvoicePdfRendererInterface::class),
            $tempPrintDataFactory,
            $this->createMock(Locale::class),
            $temporaryInvoiceStore,
        );

        $result = $service->generateForGuestByToken('test-token', 'cs', false);

        $this->assertInstanceOf(InvoiceActionResult::class, $result);
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status);
        $this->assertEquals('invoices.messages.pdf_error', $result->message);
        $this->assertNull($result->response);
    }
}
