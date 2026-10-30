<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Payment\Services;

use App\Application\Payment\Mappers\QrPaymentMapper;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Payment\Contracts\QrCodeRendererInterface;
use App\Domain\Payment\Services\QrPaymentService;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\ValueObjects\UserId;
use App\Models\BankAccount as StoredBankAccount;
use App\Models\Business;
use App\Models\Invoice;
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
        $payload = app(QrPaymentMapper::class)->fromSnapshot(
            '20260001',
            Money::fromString('1500.50', $currency),
            $snapshot,
            'Saved message',
        );
        $this->assertSame($country, $payload->account?->country->value);
        $this->assertSame($iban, $payload->account?->iban);
        $this->assertSame($snapshot->account, $payload->account);
        $this->assertSame('1500.5', $payload->amount->getAmount());
        $this->assertNotNull((new QrPaymentService($renderer))->generateQrCodeBase64($payload));
    }

    #[Test]
    public function changing_and_archiving_the_source_account_does_not_change_the_printed_payment(): void
    {
        $invoice = Invoice::factory()->create([
            'invoice_vs' => '20260042',
            'payment_amount' => '1234.56',
            'payment_currency' => 'CZK',
            'account_iban' => 'CZ6508000000192000145399',
            'account_number' => '19-2000145399',
            'account_bank_code' => '0800',
            'account_country' => 'CZ',
        ]);
        $business = Business::findOrFail($invoice->business_id);
        $source = StoredBankAccount::factory()->ownedBy($business)->create([
            'iban' => $invoice->account_iban,
            'number' => $invoice->account_number,
            'bank_code' => '0800',
        ]);
        $invoice->update(['bank_account_id' => $source->id]);
        $source->update([
            'iban' => 'DE89370400440532013000',
            'number' => null,
            'bank_code' => null,
            'country' => 'DE',
            'archived_at' => now(),
        ]);
        $business->update(['archived_at' => now()]);
        $stored = app(InvoiceDtoReadRepositoryInterface::class)->findByIdForUser(InvoiceId::fromInt($invoice->id), UserId::fromInt($invoice->user_id));
        $printable = app(InvoicePrintDataBuilderInterface::class)->build($stored);
        $payment = app(QrPaymentMapper::class)->fromPrintableInvoice($printable);
        $renderer = $this->createMock(QrCodeRendererInterface::class);
        $renderer->expects($this->once())->method('renderDataUrl')->willReturnCallback(function (string $qr): string {
            $this->assertStringContainsString('ACC:CZ6508000000192000145399*AM:1234.56*CC:CZK', $qr);
            $this->assertStringNotContainsString('DE89370400440532013000', $qr);

            return 'data:image/png;base64,' . base64_encode($qr);
        });
        $this->assertNotNull((new QrPaymentService($renderer))->generateQrCodeBase64($payment));
    }

    #[Test]
    public function a_missing_snapshot_has_no_fallback_payment_account(): void
    {
        $renderer = $this->createMock(QrCodeRendererInterface::class);
        $renderer->expects($this->never())->method('renderDataUrl');
        $payload = app(QrPaymentMapper::class)->fromSnapshot(
            '20260001',
            Money::fromString('100', 'CZK'),
            null,
            null,
        );
        $this->assertNull($payload->account);
        $this->assertNull((new QrPaymentService($renderer))->generateQrCodeBase64($payload));
    }
}
