<?php

declare(strict_types=1);

namespace App\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;

final class CzechQrPaymentProvider implements QrPaymentProviderInterface
{
    private const SUPPORTED_CURRENCIES = ['CZK'];

    public function validatePaymentInfo(QrPaymentDTO $payload): bool
    {
        return in_array(
            $payload->amount->getCurrency(),
            self::SUPPORTED_CURRENCIES,
            true,
        )
            && $payload->amountDecimal() !== '0.00'
            && $payload->account !== null;
    }

    public function generateQrString(QrPaymentDTO $payload): string
    {
        $qrParts = [];
        $qrParts[] = 'SPD*1.0';
        $qrParts[] = 'ACC:' . $payload->account?->iban;
        $qrParts[] = 'AM:' . $payload->amountDecimal();
        $qrParts[] = 'CC:' . $payload->amount->getCurrency();

        if (! empty($payload->variableSymbol)) {
            $qrParts[] = 'X-VS:' . $payload->variableSymbol;
        }

        if (! empty($payload->message)) {
            $qrParts[] = 'MSG:' . substr(
                $payload->message,
                0,
                60,
            );
        }

        return implode('*', $qrParts);
    }
}
