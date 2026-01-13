<?php

namespace App\Infrastructure\Renderers\Qr;

use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Infrastructure QR renderer using SimpleSoftwareIO/QRCode.
 */
class SimpleQrCodeRenderer implements QrCodeRendererInterface
{
    /** @inheritDoc */
    public function renderDataUrl(string $qrString, int $size = 300, int $margin = 2, string $errorCorrection = 'H'): string
    {
        $png = QrCode::format('png')
            ->size($size)
            ->margin($margin)
            ->errorCorrection($errorCorrection)
            ->generate($qrString);

        return 'data:image/png;base64,' . base64_encode($png);
    }
}
