<?php

namespace App\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentPayload;

class CzechQrPaymentProvider implements QrPaymentProviderInterface
{
    public const COUNTRY_CODE = 'CZ';
    public const SUPPORTED_CURRENCIES = ['CZK'];

    public function getCountryCode(): string
    {
        return self::COUNTRY_CODE;
    }

    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function validatePaymentInfo(QrPaymentPayload $payload): bool
    {
        if (!in_array($payload->amount->getCurrency(), $this->getSupportedCurrencies())) {
            return false;
        }

        if ($payload->amount->toFloat() <= 0) {
            return false;
        }

        if (empty($payload->iban) && (empty($payload->accountNumber) || empty($payload->bankCode))) {
            return false;
        }

        return true;
    }

    public function generateQrString(QrPaymentPayload $payload): string
    {
        $qrParts = [];
        $qrParts[] = "SPD*1.0";
        $qrParts[] = "ACC:" . $this->formatAccountIdentifier($payload->accountNumber, $payload->bankCode, $payload->iban);
        $qrParts[] = "AM:" . number_format($payload->amount->toFloat(), 2, '.', '');
        $qrParts[] = "CC:" . $payload->amount->getCurrency();

        if (!empty($payload->variableSymbol)) {
            $qrParts[] = "X-VS:" . $payload->variableSymbol;
        }

        if (!empty($payload->message)) {
            $qrParts[] = "MSG:" . substr($payload->message, 0, 60);
        }

        return implode('*', $qrParts);
    }

    public function formatAccountIdentifier(?string $accountNumber, ?string $bankCode, ?string $iban): ?string
    {
        if (!empty($iban)) {
            return strtoupper(preg_replace('/\s+/', '', $iban));
        }

        if (!empty($accountNumber) && !empty($bankCode)) {
            return $accountNumber . '/' . $bankCode;
        }

        return null;
    }

    public function validateAmount(float $amount, string $currency): bool
    {
        return $amount > 0 && in_array($currency, $this->getSupportedCurrencies());
    }
}
