<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\QrPaymentDTO;

interface QrPaymentProviderInterface
{
    /**
     * Generate QR payment string according to country-specific banking standards
     *
     * @param  QrPaymentDTO  $payload  Payment data for QR generation
     * @return string QR payment string
     */
    public function generateQrString(QrPaymentDTO $payload): string;

    /**
     * Validate if payload has all required information for QR payment in this country
     *
     * @param  QrPaymentDTO  $payload  Payment data for validation
     */
    public function validatePaymentInfo(QrPaymentDTO $payload): bool;
}
