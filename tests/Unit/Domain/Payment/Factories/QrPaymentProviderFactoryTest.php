<?php

namespace Tests\Unit\Domain\Payment\Factories;

use App\Domain\Payment\Factories\QrPaymentProviderFactory;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

class QrPaymentProviderFactoryTest extends TestCase
{
    public function test_create_czech_provider()
    {
        $provider = QrPaymentProviderFactory::createProvider('CZ');
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
        $this->assertEquals('CZ', $provider->getCountryCode());
    }

    public function test_create_slovak_provider()
    {
        $provider = QrPaymentProviderFactory::createProvider('SK');
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
        $this->assertEquals('SK', $provider->getCountryCode());
    }

    public function test_create_european_provider()
    {
        $provider = QrPaymentProviderFactory::createProvider('EU');
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
        $this->assertEquals('EU', $provider->getCountryCode());
    }

    public function test_create_provider_case_insensitive()
    {
        $provider1 = QrPaymentProviderFactory::createProvider('cz');
        $provider2 = QrPaymentProviderFactory::createProvider('CZ');
        
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider1);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider2);
    }

    public function test_create_provider_throws_exception_for_unsupported_country()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported country code: XX');
        
        QrPaymentProviderFactory::createProvider('XX');
    }

    public function test_auto_detect_provider_with_explicit_country_code()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: 'SK',
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_czech_iban()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'CZ6508000000192000145399',
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_slovak_iban()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0900',
            iban: 'SK3112000000198742637541',
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_german_iban()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: null,
            bankCode: null,
            iban: 'DE89370400440532013000',
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_czk_currency()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_eur_currency_with_slovak_bank()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('250.75', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0900', // Slovak bank code
            iban: null,
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_from_eur_currency_without_slovak_bank()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('250.75', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '3000', // Non-Slovak bank code
            iban: null,
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_provider_defaults_to_czech()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'USD'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    public function test_get_available_providers()
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

    public function test_has_provider()
    {
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('CZ'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('SK'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('EU'));
        $this->assertFalse(QrPaymentProviderFactory::hasProvider('XX'));
        
        // Test case insensitive
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('cz'));
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('sk'));
    }

    public function test_register_new_provider()
    {
        $mockProviderClass = 'App\\Domain\\Payment\\Providers\\QrPayment\\TestQrPaymentProvider';
        QrPaymentProviderFactory::registerProvider('TEST', $mockProviderClass);
        $this->assertTrue(QrPaymentProviderFactory::hasProvider('TEST'));
        $providers = QrPaymentProviderFactory::getAvailableProviders();
        $this->assertArrayHasKey('TEST', $providers);
        $this->assertEquals($mockProviderClass, $providers['TEST']);
    }

    public function test_auto_detect_with_iban_spaces()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'CZ65 0800 0000 1920 0014 5399', // IBAN with spaces
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(CzechQrPaymentProvider::class, $provider);
    }

    public function test_auto_detect_with_lowercase_iban()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'sk3112000000198742637541', // Lowercase IBAN
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(SlovakQrPaymentProvider::class, $provider);
    }

    public function test_fallback_to_european_for_unknown_european_country()
    {
        $invoice = new QrPaymentPayload(
            variableSymbol: '2024001',
            amount: Money::fromString('100.00', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: 'DE89370400440532013000', // German IBAN
            countryCode: null,
            message: 'Test payment'
        );

        $provider = QrPaymentProviderFactory::autoDetectProvider($invoice);
        $this->assertInstanceOf(EuropeanQrPaymentProvider::class, $provider);
    }
}
