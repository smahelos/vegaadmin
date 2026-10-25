<?php

namespace App\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentPayload;

class EuropeanQrPaymentProvider implements QrPaymentProviderInterface
{
    public const SUPPORTED_CURRENCIES = ['EUR'];

    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function validatePaymentInfo(QrPaymentPayload $payload): bool
    {
        if (! in_array($payload->amount->getCurrency(), $this->getSupportedCurrencies())) {
            return false;
        }

        if ($payload->amount->toFloat() <= 0) {
            return false;
        }

        // European standard requires IBAN
        if (empty($payload->iban)) {
            return false;
        }

        return true;
    }

    public function generateQrString(QrPaymentPayload $payload): string
    {
        $qrParts = [];
        $qrParts[] = 'BCD';
        $qrParts[] = '002';
        $qrParts[] = '1';
        $qrParts[] = 'SCT';
        $qrParts[] = '';
        $qrParts[] = '';
        $qrParts[] = strtoupper(preg_replace('/\s+/', '', $payload->iban));
        $qrParts[] = 'EUR' . number_format(
            $payload->amount->toFloat(),
            2,
            '.',
            '',
        );
        $qrParts[] = '';
        $qrParts[] = $payload->variableSymbol ?? '';
        $qrParts[] = substr(
            $payload->message ?? '',
            0,
            140,
        );

        return implode("\n", $qrParts);
    }
}
