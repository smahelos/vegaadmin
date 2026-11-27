<?php

declare(strict_types=1);

namespace App\Application\Invoice\Support;

use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Domain\Invoice\Contracts\DocumentPdfStorageInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceIssue;
use App\Domain\Invoice\Exceptions\InvoicePdfDamagedException;
use App\Domain\Shared\Log\Contracts\LogInterface;

/**
 * The PDF stored at issue is the document as delivered: it is returned only when its hash matches (D-050, point 8).
 */
final class StoredInvoicePdf
{
    public function __construct(
        private readonly DocumentPdfStorageInterface $storage,
        private readonly LogInterface $log,
    ) {}

    /** @throws InvoicePdfDamagedException */
    public function read(InvoiceDTO $invoice, InvoiceIssue $issue): PdfInvoiceDTO
    {
        $pdf = $issue->pdf;
        $content = null;
        if ($pdf !== null) {
            $content = $this->storage->read($pdf->path);
        }
        if ($pdf === null || $content === null || !$pdf->matches($content)) {
            $this->log->log(
                'critical',
                'Stored invoice PDF is missing or its hash does not match',
                ['invoiceId' => $invoice->id->value, 'path' => $pdf?->path],
            );

            throw new InvoicePdfDamagedException('The stored PDF of an issued invoice is damaged');
        }

        return new PdfInvoiceDTO(
            'faktura-' . $issue->number->value . '.pdf',
            $content,
            $invoice->language->value,
        );
    }
}
