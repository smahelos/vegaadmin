<?php

declare(strict_types=1);

namespace App\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Shared\Money\Enums\Currency;

final class EuropeanQrPaymentProvider implements QrPaymentProviderInterface
{
    private const SUPPORTED_CURRENCIES = [Currency::EUR];

    public function validatePaymentInfo(QrPaymentDTO $payload): bool
    {
        return in_array(
            $payload->amount->currency,
            self::SUPPORTED_CURRENCIES,
            true,
        )
            && $payload->amountDecimal() !== '0.00'
            && $payload->account !== null;
    }

    public function generateQrString(QrPaymentDTO $payload): string
    {
        $qrParts = [];
        $qrParts[] = 'BCD';
        $qrParts[] = '002';
        $qrParts[] = '1';
        $qrParts[] = 'SCT';
        $qrParts[] = '';
        $qrParts[] = '';
        $qrParts[] = $payload->account?->iban;
        $qrParts[] = $payload->amount->currency->value . $payload->amountDecimal();
        $qrParts[] = '';
        $qrParts[] = $payload->variableSymbol?->value ?? '';
        $qrParts[] = substr(
            $payload->message ?? '',
            0,
            140,
        );

        return implode("\n", $qrParts);
    }
}
