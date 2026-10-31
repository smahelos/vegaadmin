<?php

declare(strict_types=1);

namespace App\Domain\Payment\Factories;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use InvalidArgumentException;

final class QrPaymentProviderFactory
{
    /**
     * Available QR payment providers
     */
    private static array $providers = [
        'CZ' => CzechQrPaymentProvider::class,
        'SK' => SlovakQrPaymentProvider::class,
        'EU' => EuropeanQrPaymentProvider::class, // Fallback for other European countries
    ];

    /**
     * IBAN country codes mapping to providers
     * For automatic detection based on IBAN
     */
    private static array $ibanCountryMapping = [
        'CZ' => 'CZ',
        'SK' => 'SK',
        // European countries that use standard SEPA
        'DE' => 'EU',
        'FR' => 'EU',
        'IT' => 'EU',
        'ES' => 'EU',
        'NL' => 'EU',
        'BE' => 'EU',
        'AT' => 'EU',
        'PT' => 'EU',
        'GR' => 'EU',
        'IE' => 'EU',
        'FI' => 'EU',
        'LU' => 'EU',
        'SI' => 'EU',
        'CY' => 'EU',
        'MT' => 'EU',
        'EE' => 'EU',
        'LV' => 'EU',
        'LT' => 'EU',
        'PL' => 'EU',
        'HU' => 'EU',
        'RO' => 'EU',
        'BG' => 'EU',
        'HR' => 'EU',
        'DK' => 'EU',
        'SE' => 'EU',
    ];

    /**
     * Create QR payment provider based on country code
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     *
     * @throws InvalidArgumentException
     */
    public static function createProvider(string $countryCode): QrPaymentProviderInterface
    {
        $countryCode = strtoupper($countryCode);

        if (! isset(self::$providers[$countryCode])) {

            // Fallback to European provider for unknown European countries
            if (in_array($countryCode, array_keys(self::$ibanCountryMapping))) {
                $countryCode = 'EU';
            } else {
                throw new InvalidArgumentException("Unsupported country code: {$countryCode}");
            }
        }

        $providerClass = self::$providers[$countryCode];

        if (! class_exists($providerClass)) {
            throw new InvalidArgumentException("Provider class not found: {$providerClass}");
        }

        return new $providerClass;
    }

    /** Prefer the validated account country; currency is only a fallback when no supported country is known. */
    public static function autoDetectProvider(QrPaymentDTO $payload): QrPaymentProviderInterface
    {
        $country = $payload->account?->country->value;
        if ($country !== null && (self::hasProvider($country) || isset(self::$ibanCountryMapping[$country]))) {
            return self::createProvider($country);
        }

        return self::createProvider($payload->amount->getCurrency() === 'EUR' ? 'EU' : 'CZ');
    }

    /**
     * Check if provider exists for country code
     */
    public static function hasProvider(string $countryCode): bool
    {
        return isset(self::$providers[strtoupper($countryCode)]);
    }
}
