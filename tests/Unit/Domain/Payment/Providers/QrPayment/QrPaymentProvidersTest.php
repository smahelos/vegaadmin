<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\Providers\QrPayment;

use App\Domain\Payment\Contracts\QrPaymentProviderInterface;
use App\Domain\Payment\DTO\QrPaymentDTO;
use App\Domain\Payment\Providers\QrPayment\CzechQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\EuropeanQrPaymentProvider;
use App\Domain\Payment\Providers\QrPayment\SlovakQrPaymentProvider;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use App\Domain\Shared\Money\ValueObjects\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class QrPaymentProvidersTest extends TestCase
{
    /** @return array<string, array{QrPaymentProviderInterface, string, string}> */
    public static function providers(): array
    {
        return [
            'CZ' => [
                new CzechQrPaymentProvider,
                'CZK',
                'CZ6508000000192000145399',
            ],
            'SK' => [
                new SlovakQrPaymentProvider,
                'EUR',
                'SK0209000000002000145399',
            ],
            'EU' => [
                new EuropeanQrPaymentProvider,
                'EUR',
                'DE89370400440532013000',
            ],
        ];
    }

    #[Test]
    #[DataProvider('providers')]
    public function generates_from_a_validated_iban_without_losing_cents(
        QrPaymentProviderInterface $provider,
        string $currency,
        string $iban,
    ): void {
        $account = BankAccount::fromParts(
            strtolower($iban),
            null,
            null,
            null,
            null,
        );
        $payment = new QrPaymentDTO(
            '20260001',
            Money::fromString('9007199254740993.01', $currency),
            $account,
            'Stored message',
        );
        $this->assertTrue($provider->validatePaymentInfo($payment));
        $qr = $provider->generateQrString($payment);
        if ($provider instanceof EuropeanQrPaymentProvider) {
            $this->assertSame("BCD\n002\n1\nSCT\n\n\nDE89370400440532013000\nEUR9007199254740993.01\n\n20260001\nStored message", $qr);
        } else {
            $this->assertSame('SPD*1.0*ACC:' . $iban . '*AM:9007199254740993.01*CC:' . $currency . '*X-VS:20260001*MSG:Stored message', $qr);
        }
    }

    #[Test]
    #[DataProvider('providers')]
    public function rejects_missing_accounts_zero_rounded_amounts_and_unsupported_currency(
        QrPaymentProviderInterface $provider,
        string $currency,
        string $iban,
    ): void {
        $account = BankAccount::fromParts(
            $iban,
            null,
            null,
            null,
            null,
        );
        $this->assertFalse($provider->validatePaymentInfo(new QrPaymentDTO(null, Money::fromString('10', $currency), null)));
        $this->assertFalse($provider->validatePaymentInfo(new QrPaymentDTO(null, Money::fromString('0', $currency), $account)));
        $this->assertFalse($provider->validatePaymentInfo(new QrPaymentDTO(null, Money::fromString('0.004', $currency), $account)));
        $this->assertFalse($provider->validatePaymentInfo(new QrPaymentDTO(null, Money::fromString('10', 'USD'), $account)));
        $this->assertTrue($provider->validatePaymentInfo(new QrPaymentDTO(null, Money::fromString('0.005', $currency), $account)));
    }

    #[Test]
    public function local_czech_and_slovak_accounts_use_the_iban_computed_by_bank_account(): void
    {
        foreach ([Country::CZ, Country::SK] as $country) {
            $provider = $country === Country::CZ ? new CzechQrPaymentProvider : new SlovakQrPaymentProvider;
            $account = BankAccount::fromParts(
                null,
                '19-2000145399',
                '0800',
                $country,
                null,
            );
            $payment = new QrPaymentDTO(
                null,
                Money::fromString('12', $country === Country::CZ ? 'CZK' : 'EUR'),
                $account,
            );
            $this->assertStringContainsString('ACC:' . $account->iban . '*AM:12.00', $provider->generateQrString($payment));
        }
    }

    /** @return list<array{string, string}> */
    public static function amounts(): array
    {
        return [
            ['12', '12.00'],
            ['12.5', '12.50'],
            ['1.005', '1.01'],
            ['2.675', '2.68'],
            ['999999999999.995', '1000000000000.00'],
            ['0.004', '0.00'],
            ['0.005', '0.01'],
        ];
    }

    #[Test]
    #[DataProvider('amounts')]
    public function rounds_half_up_and_pads_decimal_amounts(string $amount, string $expected): void
    {
        $payment = new QrPaymentDTO(
            null,
            Money::fromString($amount, 'CZK'),
            null,
        );
        $this->assertSame($expected, $payment->amountDecimal());
    }
}
