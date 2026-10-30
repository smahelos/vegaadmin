<?php

declare(strict_types=1);

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\QrPaymentDTO;

interface QrPaymentServiceInterface
{
    /**
     * Generate QR code for invoice payment in base64 format
     *
     * @param  QrPaymentDTO  $payload  Payment data for QR generation
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64(QrPaymentDTO $payload): ?string;
}
