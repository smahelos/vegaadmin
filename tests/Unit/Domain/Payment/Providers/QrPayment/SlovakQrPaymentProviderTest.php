<?php

namespace Tests\Unit\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SlovakQrPaymentProviderTest extends TestCase
{
    private SlovakQrPaymentProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new SlovakQrPaymentProvider();
    }

    #[Test]
    public function country_code_is_slovak(): void
    {
        $this->assertEquals('SK', $this->provider->getCountryCode());
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
            accountNumber: '1234567890',
            bankCode: '0900',
            iban: null,
            message: 'Test payment'
        );

        $this->assertTrue($this->provider->validatePaymentInfo($payload));
    }

    #[Test]
    public function generate_qr_string(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'EUR'),
            accountNumber: '1234567890',
            bankCode: '0900',
            iban: null,
            message: 'Test payment'
        );

        $qrString = $this->provider->generateQrString($payload);
        
        $this->assertStringStartsWith('SPD*1.0', $qrString);
        $this->assertStringContainsString('ACC:1234567890/0900', $qrString);
        $this->assertStringContainsString('AM:1234.56', $qrString);
        $this->assertStringContainsString('CC:EUR', $qrString);
    }
}
