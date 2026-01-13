<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\DTO\QrPaymentPayload;

interface QrPaymentProviderInterface
{
    /**
     * Generate QR payment string according to country-specific banking standards
     * 
     * @param QrPaymentPayload $payload Payment data for QR generation
     * @return string QR payment string
     */
    public function generateQrString(QrPaymentPayload $payload): string;

    /**
     * Validate if payload has all required information for QR payment in this country
     *
     * @param QrPaymentPayload $payload Payment data for validation
     * @return bool
     */
    public function validatePaymentInfo(QrPaymentPayload $payload): bool;

    /**
     * Get the country code this provider supports
     *
     * @return string Country code (ISO 3166-1 alpha-2)
     */
    public function getCountryCode(): string;

    /**
     * Get supported currency codes for this country
     *
     * @return array Array of supported currency codes
     */
    public function getSupportedCurrencies(): array;

    /**
     * Validate and format account number/IBAN for this country
     *
     * @param string|null $accountNumber Account number
     * @param string|null $bankCode Bank code
     * @param string|null $iban IBAN
     * @return string|null Formatted account identifier or null if invalid
     */
    public function formatAccountIdentifier(?string $accountNumber, ?string $bankCode, ?string $iban): ?string;

    /**
     * Validate amount for this country's banking standards
     *
     * @param float $amount Payment amount
     * @param string $currency Currency code
     * @return bool
     */
    public function validateAmount(float $amount, string $currency): bool;
}
