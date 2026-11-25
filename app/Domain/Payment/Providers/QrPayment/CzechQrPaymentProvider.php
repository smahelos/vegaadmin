<?php

declare(strict_types=1);

namespace App\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Shared\Money\Enums\Currency;

final class CzechQrPaymentProvider implements QrPaymentProviderInterface
{
    private const SUPPORTED_CURRENCIES = [Currency::CZK];

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
        $qrParts[] = 'SPD*1.0';
        $qrParts[] = 'ACC:' . $payload->account?->iban;
        $qrParts[] = 'AM:' . $payload->amountDecimal();
        $qrParts[] = 'CC:' . $payload->amount->currency->value;

        if ($payload->variableSymbol !== null) {
            $qrParts[] = 'X-VS:' . $payload->variableSymbol->value;
        }

        $message = $payload->asciiMessage(60);
        if ($message !== '') {
            // The asterisk separates the fields; the format escapes it in a value
            $qrParts[] = 'MSG:' . str_replace(
                '*',
                '%2A',
                $message,
            );
        }

        return implode('*', $qrParts);
    }
}
