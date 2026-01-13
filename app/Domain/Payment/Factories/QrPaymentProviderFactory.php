<?php

namespace App\Domain\Payment\Factories;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\DTO\QrPaymentPayload;

class QrPaymentProviderFactory
{
    /**
     * Available QR payment providers
     *
     * @var array
     */
    private static array $providers = [
        'CZ' => CzechQrPaymentProvider::class,
        'SK' => SlovakQrPaymentProvider::class,
        'EU' => EuropeanQrPaymentProvider::class, // Fallback for other European countries
    ];

    /**
     * IBAN country codes mapping to providers
     * For automatic detection based on IBAN
     *
     * @var array
     */
    private static array $ibanCountryMapping = [
        'CZ' => 'CZ',
        'SK' => 'SK',
        // European countries that use standard SEPA
        'DE' => 'EU', 'FR' => 'EU', 'IT' => 'EU', 'ES' => 'EU', 'NL' => 'EU',
        'BE' => 'EU', 'AT' => 'EU', 'PT' => 'EU', 'GR' => 'EU', 'IE' => 'EU',
        'FI' => 'EU', 'LU' => 'EU', 'SI' => 'EU', 'CY' => 'EU', 'MT' => 'EU',
        'EE' => 'EU', 'LV' => 'EU', 'LT' => 'EU', 'PL' => 'EU', 'HU' => 'EU',
        'RO' => 'EU', 'BG' => 'EU', 'HR' => 'EU', 'DK' => 'EU', 'SE' => 'EU',
    ];

    /**
     * Create QR payment provider based on country code
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return QrPaymentProviderInterface
     * @throws \InvalidArgumentException
     */
    public static function createProvider(string $countryCode): QrPaymentProviderInterface
    {
        $countryCode = strtoupper($countryCode);
        
        if (!isset(self::$providers[$countryCode])) {
            
            // Fallback to European provider for unknown European countries
            if (in_array($countryCode, array_keys(self::$ibanCountryMapping))) {
                $countryCode = 'EU';
            } else {
                throw new \InvalidArgumentException("Unsupported country code: {$countryCode}");
            }
        }

        $providerClass = self::$providers[$countryCode];
        
        if (!class_exists($providerClass)) {
            throw new \InvalidArgumentException("Provider class not found: {$providerClass}");
        }

        return new $providerClass();
    }

    /**
     * Auto-detect provider based on payment payload data
     * Priority: 1. IBAN country 2. Currency 3. Default (CZ)
     *
     * @param QrPaymentPayload $payload Payment data
     * @return QrPaymentProviderInterface
     */
    public static function autoDetectProvider(QrPaymentPayload $payload): QrPaymentProviderInterface
    {
        // Priority 1: Detect from Country Code if provided
        if (!empty($payload->countryCode) && self::hasProvider($payload->countryCode)) {
            return self::createProvider($payload->countryCode);
        }

        // Priority 2: Detect from IBAN
        if (!empty($payload->iban)) {
            $ibanCountryCode = strtoupper(substr(preg_replace('/\s+/', '', $payload->iban), 0, 2));
            
            if (isset(self::$ibanCountryMapping[$ibanCountryCode])) {
                $providerCountryCode = self::$ibanCountryMapping[$ibanCountryCode];
                
                try {
                    return self::createProvider($providerCountryCode);
                } catch (\InvalidArgumentException $e) {
                    // Country not supported, continue
                }
            }
        }

        // Priority 3: Detect from currency (basic heuristic)
        $currency = $payload->amount->getCurrency();
        if (!empty($currency)) {
            switch (strtoupper($currency)) {
                case 'CZK':
                    return self::createProvider('CZ');
                case 'EUR':
                    // EUR is used in many countries, prefer Slovak if we have Slovak bank details
                    if (!empty($payload->bankCode) && self::isSlovakBankCode($payload->bankCode)) {
                        return self::createProvider('SK');
                    }
                    return self::createProvider('EU');
                default:
                    return self::createProvider('CZ');
            }
        }

        // Priority 3: Default to Czech provider
        return self::createProvider('CZ');
    }

    /**
     * Check if bank code belongs to Slovak bank
     * This is a simplified check - in real implementation you'd have a complete list
     *
     * @param string $bankCode
     * @return bool
     */
    private static function isSlovakBankCode(string $bankCode): bool
    {
        // Common Slovak bank codes (simplified list)
        $slovakBankCodes = [
            '0900', // Slovenska sporitelna
            '1100', // Tatrabanka
            '1111', // UniCredit Bank
            '3100', // SLSP
            '5200', // OTP Banka
            '6500', // Postova banka
            '7500', // CSOB
            '8120', // CSOB
        ];
        
        return in_array($bankCode, $slovakBankCodes);
    }

    /**
     * Get all available providers
     *
     * @return array
     */
    public static function getAvailableProviders(): array
    {
        return self::$providers;
    }

    /**
     * Register a new provider
     *
     * @param string $countryCode
     * @param string $providerClass
     * @return void
     */
    public static function registerProvider(string $countryCode, string $providerClass): void
    {
        self::$providers[strtoupper($countryCode)] = $providerClass;
    }

    /**
     * Check if provider exists for country code
     *
     * @param string $countryCode
     * @return bool
     */
    public static function hasProvider(string $countryCode): bool
    {
        return isset(self::$providers[strtoupper($countryCode)]);
    }
}
