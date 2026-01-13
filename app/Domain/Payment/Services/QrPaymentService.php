<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\QrPaymentServiceInterface;
use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Payment\Factories\QrPaymentProviderFactory;

class QrPaymentService implements QrPaymentServiceInterface
{
    public function __construct(
        private readonly QrCodeRendererInterface $qrRenderer,
    ) {}

    /**
     * Generate QR code for invoice payment in base64 format
     * 
     * @param QrPaymentPayload $payload Payment data for QR generation
     * @return string|null Base64 encoded QR code image
     */
    public function generateQrCodeBase64(QrPaymentPayload $payload): ?string
    {
        // Auto-detect appropriate provider based on payload data
        $provider = QrPaymentProviderFactory::autoDetectProvider($payload);

        // Validate payment data using country-specific provider
        if (!$provider->validatePaymentInfo($payload)) {
            return null;
        }

        // Generate QR payload string via provider and render it via injected renderer
        $qrString = $provider->generateQrString($payload);
        return $this->qrRenderer->renderDataUrl($qrString);
    }

    /**
     * Check if payment payload has all required information for QR payment
     *
     * @param QrPaymentPayload $payload
     * @return bool
     */
    public function hasRequiredPaymentInfo(QrPaymentPayload $payload): bool
    {
        $provider = QrPaymentProviderFactory::autoDetectProvider($payload);
        return $provider->validatePaymentInfo($payload);
    }

    /**
     * Get supported countries for QR payments
     * 
     * @return array Array of country codes
     */
    public function getSupportedCountries(): array
    {
        return array_keys(QrPaymentProviderFactory::getAvailableProviders());
    }
}
