<?php

namespace App\Contracts;

use App\Models\Invoice;

interface InvoicePdfServiceInterface
{
    /**
     * Generate PDF from invoice
     * 
     * @param Invoice $invoice
     * @param string|null $requestLocale
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(Invoice $invoice, ?string $requestLocale = null);

    /**
     * Generate PDF from temporary invoice data
     * 
     * @param array $invoiceData
     * @param string|null $requestLocale
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdfFromData(array $invoiceData, ?string $requestLocale = null);
}
