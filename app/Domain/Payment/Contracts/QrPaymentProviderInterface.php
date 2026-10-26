<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\QrPaymentPayload;

interface QrPaymentProviderInterface
{
    /**
     * Generate QR payment string according to country-specific banking standards
     *
     * @param  QrPaymentPayload  $payload  Payment data for QR generation
     * @return string QR payment string
     */
    public function generateQrString(QrPaymentPayload $payload): string;

    /**
     * Validate if payload has all required information for QR payment in this country
     *
     * @param  QrPaymentPayload  $payload  Payment data for validation
     */
    public function validatePaymentInfo(QrPaymentPayload $payload): bool;
}
