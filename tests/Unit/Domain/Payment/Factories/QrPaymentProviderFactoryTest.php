<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Factories;

use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Payment\Factories\QrPaymentProviderFactory;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QrPaymentProviderFactoryTest extends TestCase
{
    #[Test]
    public function create_czech_provider(): void
    {
        $provider = QrPaymentProviderFactory::createProvider('CZ');
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
        $this->assertEquals('CZ', $provider->getCountryCode());
    }

    #[Test]
    public function create_slovak_provider(): void
    {
        $provider = QrPaymentProviderFactory::createProvider('SK');
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
        $this->assertEquals('SK', $provider->getCountryCode());
    }

    #[Test]
    public function create_european_provider(): void
    {
        $provider = QrPaymentProviderFactory::createProvider('EU');
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
        $this->assertEquals('EU', $provider->getCountryCode());
    }

    #[Test]
    public function create_provider_case_insensitive(): void
    {
        $provider1 = QrPaymentProviderFactory::createProvider('cz');
        $provider2 = QrPaymentProviderFactory::createProvider('CZ');

        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider1);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider2);
    }

    #[Test]
    public function create_provider_throws_exception_for_unsupported_country(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported country code: XX');

        QrPaymentProviderFactory::createProvider('XX');
    }

    #[Test]
    public function auto_detect_provider_with_explicit_country_code(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: 'SK',
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_czech_iban(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'CZ6508000000192000145399',
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_slovak_iban(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0900',
            iban: 'SK3112000000198742637541',
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_german_iban(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: null,
            bankCode: null,
            iban: 'DE89370400440532013000',
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_czk_currency(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_eur_currency_with_slovak_bank(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('250.75', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0900', // Slovak bank code
            iban: null,
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_from_eur_currency_without_slovak_bank(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('250.75', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '3000', // Non-Slovak bank code
            iban: null,
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_provider_defaults_to_czech(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'USD'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function get_available_providers(): void
    {
        $providers = QrPaymentProviderFactory::getAvailableProviders();

        $this->assertIsArray($providers);
        $this->assertArrayHasKey('CZ', $providers);
        $this->assertArrayHasKey('SK', $providers);
        $this->assertArrayHasKey('EU', $providers);
        $this->assertEquals(CzechQrPaymentProvider::class, $providers['CZ']);
        $this->assertEquals(SlovakQrPaymentProvider::class, $providers['SK']);
        $this->assertEquals(EuropeanQrPaymentProvider::class, $providers['EU']);
    }

    #[Test]
    public function has_provider(): void
    {
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('CZ'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('SK'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('EU'));
        $this->assertFalse(QrPaymentProviderFactory::hasProvider('XX'));

        // Test case insensitive
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('cz'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('sk'));
    }

    #[Test]
    public function auto_detect_with_iban_spaces(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'CZ65 0800 0000 1920 0014 5399', // IBAN with spaces
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function auto_detect_with_lowercase_iban(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'sk3112000000198742637541', // Lowercase IBAN
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    #[Test]
    public function fallback_to_european_for_unknown_european_country(): void
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'DE89370400440532013000', // German IBAN
            countryCode: null,
            message: 'Test payment',
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }
}
