<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Payment\Services;

use App\Application\Payment\Mappers\QrPaymentPayloadMapper;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class QrPaymentServiceFeatureTest extends TestCase
{
    /** @return array<string, array{string, string, string, string}> */
    public static function accounts(): array
    {
        return [
            'Czech' => [
                'CZ',
                'CZK',
                'CZ6508000000192000145399',
                'SPD*1.0*',
            ],
            'Slovak' => [
                'SK',
                'EUR',
                'SK0209000000002000145399',
                'SPD*1.0*',
            ],
            'German' => [
                'DE',
                'EUR',
                'DE89370400440532013000',
                'BCD',
            ],
        ];
    }

    #[Test]
    #[DataProvider('accounts')]
    public function generates_qr_from_the_account_snapshot(
        string $country,
        string $currency,
        string $iban,
        string $prefix,
    ): void {
        $renderer = $this->createMock(QrCodeRendererInterface::class);
        $renderer->expects($this->once())->method('renderDataUrl')->willReturnCallback(
            function (string $payload) use ($prefix): string {
                $this->assertStringStartsWith($prefix, $payload);

                return 'data:image/png;base64,' . base64_encode($payload);
            },
        );
        $snapshot = new DocumentAccountDTO(BankAccount::fromParts($iban, null, null, Country::from($country), null), 'Saved bank');
        $payload = app(QrPaymentPayloadMapper::class)->fromSnapshot(
            '20260001',
            Money::fromString('1500.50', $currency),
            $snapshot,
            'Saved message',
        );
        $this->assertSame($country, $payload->countryCode);
        $this->assertSame($iban, $payload->iban);
        $this->assertSame('1500.5', $payload->amount->getAmount());
        $this->assertNotNull((new QrPaymentService($renderer))->generateQrCodeBase64($payload));
    }

    #[Test]
    public function a_missing_snapshot_has_no_fallback_payment_account(): void
    {
        $renderer = $this->createMock(QrCodeRendererInterface::class);
        $renderer->expects($this->never())->method('renderDataUrl');
        $payload = app(QrPaymentPayloadMapper::class)->fromSnapshot(
            '20260001',
            Money::fromString('100', 'CZK'),
            null,
            null,
        );
        $this->assertNull($payload->accountNumber);
        $this->assertNull($payload->iban);
        $this->assertNull((new QrPaymentService($renderer))->generateQrCodeBase64($payload));
    }
}
