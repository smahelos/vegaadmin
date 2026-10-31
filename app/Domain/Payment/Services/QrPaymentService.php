<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Factories\QrPaymentProviderFactory;

final class QrPaymentService implements QrPaymentServiceInterface
{
    public function __construct(
        private readonly QrCodeRendererInterface $qrRenderer,
    ) {}

    /**
     * Generate QR code for invoice payment in base64 format
     *
     * @param  QrPaymentDTO  $payload  Payment data for QR generation
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64(QrPaymentDTO $payload): ?string
    {
        // Auto-detect appropriate provider based on payload data
        $provider = QrPaymentProviderFactory::autoDetectProvider($payload);

        // Validate payment data using country-specific provider
        if (! $provider->validatePaymentInfo($payload)) {
            return null;
        }

        // Generate QR payload string via provider and render it via injected renderer
        $qrString = $provider->generateQrString($payload);

        return $this->qrRenderer->renderDataUrl($qrString);
    }
}
