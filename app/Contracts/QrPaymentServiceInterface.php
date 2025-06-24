<?php

namespace App\Contracts;

use App\Models\Invoice;

interface QrPaymentServiceInterface
{
    /**
     * Generate QR code for invoice payment in base64 format
     * 
     * @param mixed $invoice Invoice object or standard class with invoice data
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64($invoice): ?string;

    /**
     * Check if invoice has all required information for QR payment
     *
     * @param Invoice $invoice
     * @return bool
     */
    public function hasRequiredPaymentInfo(Invoice $invoice): bool;
}
