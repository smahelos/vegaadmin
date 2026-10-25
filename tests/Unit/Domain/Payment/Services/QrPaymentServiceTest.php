<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\DTO\QrPaymentPayload;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QrPaymentServiceTest extends TestCase
{
    private QrPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Provide a simple stub for renderer dependency
        $renderer = new class implements QrCodeRendererInterface
        {
            public function renderDataUrl(
                string $qrString,
                int $size = 300,
                int $margin = 2,
                string $errorCorrection = 'H',
            ): string {
                return 'data:image/png;base64,' . base64_encode($qrString);
            }
        };
        $this->service = new QrPaymentService($renderer);
    }

    #[Test]
    public function public_methods_exist(): void
    {
        $expected = [
            'generateQrCodeBase64',
            'hasRequiredPaymentInfo',
            'getSupportedCountries',
        ];
        $methods = get_class_methods($this->service);
        foreach ($expected as $m) {
            $this->assertContains(
                $m,
                $methods,
                "Missing method $m",
            );
        }
    }

    #[Test]
    public function generate_qr_code_base64_czech(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: 'Test payment',
        );

        $result = $this->service->generateQrCodeBase64($payload);
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);

        // Decode the base64 content to verify it contains the expected QR string
        $qrContent = base64_decode(substr($result, strlen('data:image/png;base64,')));
        $this->assertStringContainsString('SPD*1.0', $qrContent);
        $this->assertStringContainsString('X-VS:20250001', $qrContent);
    }

    #[Test]
    public function generate_qr_code_base64_european(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250002',
            amount: Money::fromString('250.75', 'EUR'),
            accountNumber: null,
            bankCode: null,
            iban: 'DE44500105175407324931',
            message: 'European payment',
        );

        $result = $this->service->generateQrCodeBase64($payload);
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);

        $qrContent = base64_decode(substr($result, strlen('data:image/png;base64,')));
        $this->assertStringContainsString('BCD', $qrContent);
        $this->assertStringContainsString('DE44500105175407324931', $qrContent);
    }

    #[Test]
    public function has_required_payment_info_valid(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('100.00', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: null,
        );

        $result = $this->service->hasRequiredPaymentInfo($payload);
        $this->assertTrue($result);
    }

    #[Test]
    public function has_required_payment_info_invalid_missing_account(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('100.00', 'CZK'),
            accountNumber: null,
            bankCode: null,
            iban: null,
            message: null,
        );

        $result = $this->service->hasRequiredPaymentInfo($payload);
        $this->assertFalse($result);
    }

    #[Test]
    public function has_required_payment_info_invalid_zero_amount(): void
    {
        $payload = new QrPaymentPayload(
            variableSymbol: '20250001',
            amount: Money::fromString('0.00', 'CZK'),
            accountNumber: '1234567890',
            bankCode: '0100',
            iban: null,
            message: null,
        );

        $result = $this->service->hasRequiredPaymentInfo($payload);
        $this->assertFalse($result);
    }

    #[Test]
    public function get_supported_countries_returns_expected_keys(): void
    {
        $countries = $this->service->getSupportedCountries();
        $this->assertIsArray($countries);
        $this->assertContains('CZ', $countries);
        $this->assertContains('SK', $countries);
        $this->assertContains('EU', $countries);
        $this->assertCount(3, $countries); // current factory providers
    }
}
