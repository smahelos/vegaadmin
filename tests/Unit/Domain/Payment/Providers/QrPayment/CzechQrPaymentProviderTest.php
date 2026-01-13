<?php

namespace Tests\Unit\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CzechQrPaymentProviderTest extends TestCase
{
    private CzechQrPaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CzechQrPaymentProvider();
    }

    #[Test]
    public function country_code_is_czech(): void
    {
        $this->assertEquals('CZ', $this->provider->getCountryCode());
    }

    #[Test]
    public function supported_currencies(): void
    {
        $currencies = $this->provider->getSupportedCurrencies();
        $this->assertContains('CZK', $currencies);
        $this->assertCount(1, $currencies); // Only CZK supported
    }

    #[Test]
    public function validate_payment_info_valid(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1000.50', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: 'Test payment'
        );

        $this->assertTrue($this->provider->validatePaymentInfo($payload));
    }

    #[Test]
    public function validate_payment_info_invalid_currency(): void
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
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: 'Test payment'
        );

        $qrString = $this->provider->generateQrString($payload);
        
        $this->assertStringStartsWith('SPD*1.0', $qrString);
        $this->assertStringContainsString('ACC:1234567890/0100', $qrString);
        $this->assertStringContainsString('AM:1234.56', $qrString);
        $this->assertStringContainsString('CC:CZK', $qrString);
        $this->assertStringContainsString('X-VS:20250001', $qrString);
    }

    #[Test]
    public function validate_amount(): void
    {
        $this->assertTrue($this->provider->validateAmount(1000.50, 'CZK'));
        $this->assertFalse($this->provider->validateAmount(0, 'CZK'));
        $this->assertFalse($this->provider->validateAmount(1000, 'EUR'));
    }
}
