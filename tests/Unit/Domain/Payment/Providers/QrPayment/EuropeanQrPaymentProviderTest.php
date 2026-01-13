<?php

namespace Tests\Unit\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EuropeanQrPaymentProviderTest extends TestCase
{
    private EuropeanQrPaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new EuropeanQrPaymentProvider();
    }

    #[Test]
    public function country_code_is_european(): void
    {
        $this->assertEquals('EU', $this->provider->getCountryCode());
    }

    #[Test]
    public function supported_currencies(): void
    {
        $currencies = $this->provider->getSupportedCurrencies();
        $this->assertContains('EUR', $currencies);
        $this->assertCount(1, $currencies);
    }

    #[Test]
    public function validate_payment_info_valid(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1000.50', 'EUR'),
            accountNumber: null,
            bankCode: null,
            iban: 'DE44500105175407324931',
            message: 'Test payment'
        );

        $this->assertTrue($this->provider->validatePaymentInfo($payload));
    }

    #[Test]
    public function validate_payment_info_invalid_no_iban(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1000.50', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: 'Test payment'
        );

        $this->assertFalse($this->provider->validatePaymentInfo($payload));
    }

    #[Test]
    public function generate_qr_string(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: null,
            bankCode: null,
            iban: 'DE44500105175407324931',
            message: 'Test payment'
        );

        $qrString = $this->provider->generateQrString($payload);
        
        $this->assertStringStartsWith('BCD', $qrString);
        $this->assertStringContainsString('DE44500105175407324931', $qrString);
        $this->assertStringContainsString('EUR1234.56', $qrString);
    }
}
