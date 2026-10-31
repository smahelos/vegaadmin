<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Factories;

use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Factories\QrPaymentProviderFactory;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Money\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QrPaymentProviderFactoryTest extends TestCase
{
    /** @return list<array{?string, string, class-string}> */
    public static function accounts(): array
    {
        return [
            [
                'CZ65 0800 0000 1920 0014 5399',
                'CZK',
                CzechQrPaymentProvider::class,
            ],
            [
                'sk0209000000002000145399',
                'EUR',
                SlovakQrPaymentProvider::class,
            ],
            [
                'DE89370400440532013000',
                'EUR',
                EuropeanQrPaymentProvider::class,
            ],
            [
                'CZ6508000000192000145399',
                'EUR',
                CzechQrPaymentProvider::class,
            ],
            [
                'DE89370400440532013000',
                'CZK',
                EuropeanQrPaymentProvider::class,
            ],
            [
                null,
                'EUR',
                EuropeanQrPaymentProvider::class,
            ],
            [
                null,
                'CZK',
                CzechQrPaymentProvider::class,
            ],
            [
                null,
                'USD',
                CzechQrPaymentProvider::class,
            ],
        ];
    }

    #[Test]
    #[DataProvider('accounts')]
    public function detects_from_the_validated_account_country_before_currency(
        ?string $iban,
        string $currency,
        string $providerClass,
    ): void {
        $account = $iban === null ? null : BankAccount::fromParts(
            $iban,
            null,
            null,
            null,
            null,
        );
        $payment = new QrPaymentDTO(
            '20260001',
            Money::fromString('12.50', $currency),
            $account,
        );
        $this->assertInstanceOf($providerClass, QrPaymentProviderFactory::autoDetectProvider($payment));
    }

    #[Test]
    public function explicit_provider_selection_is_case_insensitive_and_keeps_european_fallback(): void
    {
        $this->assertInstanceOf(CzechQrPaymentProvider::class, QrPaymentProviderFactory::createProvider('cz'));
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, QrPaymentProviderFactory::createProvider('SK'));
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, QrPaymentProviderFactory::createProvider('DE'));
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, QrPaymentProviderFactory::createProvider('EU'));
    }

    #[Test]
    public function explicit_provider_selection_rejects_unsupported_countries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        QrPaymentProviderFactory::createProvider('XX');
    }
}
