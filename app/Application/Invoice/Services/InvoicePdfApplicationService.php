<?php

namespace App\Application\Invoice\Services;

use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfRendererInterface;
use App\Application\Invoice\Contracts\TemporaryInvoiceStoreInterface;
use App\Application\Invoice\Contracts\TemporaryInvoicePrintDataFactoryInterface;
use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\Contracts\Locale;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class InvoicePdfApplicationService implements InvoicePdfApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly InvoiceDtoReadRepositoryInterface $invoiceDtoReadRepository,
        private readonly InvoicePrintDataBuilderInterface $printDataBuilder,
        private readonly InvoicePdfRendererInterface $invoicePdfRenderer,
        private readonly TemporaryInvoicePrintDataFactoryInterface $tempPrintDataFactory,
        private readonly Locale $locale,
        private readonly TemporaryInvoiceStoreInterface $temporaryInvoiceStore,
    ) {}

    public function generateForUser(int $userId, int $invoiceId, ?string $locale = null, bool $preview = false): InvoiceActionResult
    {
        try {
            $dto = $this->invoiceDtoReadRepository->findByIdForUser(InvoiceId::fromInt($invoiceId), UserId::fromInt($userId));
            if (!$dto) { throw new ModelNotFoundException('Invoice not accessible for user'); }
            $printData = $this->printDataBuilder->build($dto);
            $pdf = $this->invoicePdfRenderer->render($printData, $printData->template ?? null, $locale);
            $filename = 'faktura-' . ($printData->number ?? (string)$invoiceId) . '.pdf';
            $response = $preview ? $pdf->stream($filename) : $pdf->download($filename);
            $response = $response->cookie('locale', $locale ?? app()->getLocale(), 60 * 24 * 30);
            return InvoiceActionResult::success(response: $response);
        } catch (ModelNotFoundException $e) {
            return InvoiceActionResult::failure('invoices.messages.show_error');
        } catch (\Exception $e) {
            Log::error('Error generating user invoice PDF: ' . $e->getMessage());
            return InvoiceActionResult::failure('invoices.messages.pdf_error');
        }
    }

    public function generateForGuestByToken(?string $token, ?string $requestedLocale, bool $preview = false): InvoiceActionResult
    {
        try {
            if (!$token) {
                $token = session()->get('last_guest_invoice_token');
                if (!$token) {
                    return InvoiceActionResult::failure('invoices.messages.not_found');
                }
            }
            Log::info('Starting PDF generation with token: ' . substr($token, 0, 8) . '...');
            // Use application-level store to retrieve guest invoice data
            $invoiceData = $this->temporaryInvoiceStore->get($token);
            if (!$invoiceData) {
                return InvoiceActionResult::failure('invoices.messages.expired');
            }
            $printData = $this->tempPrintDataFactory->fromArray($invoiceData);
            $pdf = $this->invoicePdfRenderer->render($printData, $printData->template ?? null, $requestedLocale);
            $filename = 'faktura-' . ($printData->number ?? date('YmdHis')) . '.pdf';
            $response = $preview ? $pdf->stream($filename) : $pdf->download($filename);
            $locale = $this->locale->determineLocale($requestedLocale, $invoiceData['lang'] ?? null);
            $response = $response->cookie('locale', $locale, 60 * 24 * 30);
            return InvoiceActionResult::success(response: $response);
        } catch (\Exception $e) {
            Log::error('Error generating guest invoice PDF: ' . $e->getMessage());
            return InvoiceActionResult::failure('invoices.messages.pdf_error');
        }
    }

    public function generate(?int $userId, ?int $invoiceId = null, ?string $token = null, ?string $locale = null, bool $preview = false): InvoiceActionResult
    {
        if ($userId && $invoiceId !== null) {
            return $this->generateForUser($userId, $invoiceId, $locale, $preview);
        }
        return $this->generateForGuestByToken($token, $locale, $preview);
    }
}
