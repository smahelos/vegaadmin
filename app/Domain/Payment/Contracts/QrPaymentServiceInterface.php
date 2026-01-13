<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\QrPaymentPayload;

interface QrPaymentServiceInterface
{
    /**
     * Generate QR code for invoice payment in base64 format
     * 
     * @param QrPaymentPayload $payload Payment data for QR generation
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64(QrPaymentPayload $payload): ?string;

    /**
     * Check if payment payload has all required information for QR payment
     *
     * @param QrPaymentPayload $payload
     * @return bool
     */
    public function hasRequiredPaymentInfo(QrPaymentPayload $payload): bool;
}
