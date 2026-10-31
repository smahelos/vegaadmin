<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Services;

use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Payment\ValueObjects\BankAccount;
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
    public function generate_qr_code_base64_czech(): void
    {
        $payload = new QrPaymentDTO(
            variableSymbol: '20250001',
            amount: Money::fromString('1234.56', 'CZK'),
            account: BankAccount::fromParts(
                'CZ6508000000192000145399',
                null,
                null,
                null,
                null,
            ),
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
        $payload = new QrPaymentDTO(
            variableSymbol: '20250002',
            amount: Money::fromString('250.75', 'EUR'),
            account: BankAccount::fromParts(
                'DE44500105175407324931',
                null,
                null,
                null,
                null,
            ),
            message: 'European payment',
        );

        $result = $this->service->generateQrCodeBase64($payload);
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);

        $qrContent = base64_decode(substr($result, strlen('data:image/png;base64,')));
        $this->assertStringContainsString('BCD', $qrContent);
        $this->assertStringContainsString('DE44500105175407324931', $qrContent);
    }
}
