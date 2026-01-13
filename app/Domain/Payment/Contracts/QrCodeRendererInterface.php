<?php

namespace App\Domain\Payment\Contracts;

/**
 * Renders QR code images from QR payload strings.
 * This contract allows Domain services to remain framework-agnostic.
 */
interface QrCodeRendererInterface
{
    /**
     * Render QR code as data URL (PNG base64) for embedding in HTML/PDF.
     *
     * @param string $qrString QR payload string to encode
     * @param int $size Pixel size of generated image
     * @param int $margin Margin in pixels around the QR
     * @param string $errorCorrection Error correction level (e.g. L, M, Q, H)
     * @return string Data URL string (e.g. "data:image/png;base64,...")
     */
    public function renderDataUrl(string $qrString, int $size = 300, int $margin = 2, string $errorCorrection = 'H'): string;
}
