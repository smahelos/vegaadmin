<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Payment\ValueObjects;

use App\Domain\Payment\Enums\BankAccountProblem;
use App\Domain\Payment\Exceptions\InvalidBankAccountException;
use App\Domain\Payment\ValueObjects\BankAccount;
use App\Domain\Shared\Geography\Enums\Country;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BankAccountTest extends TestCase
{
    #[Test]
    public function a_czech_local_number_gets_its_iban(): void
    {
        $account = BankAccount::fromParts(
            null,
            '19-2000145399',
            '0800',
            Country::CZ,
            null,
        );

        $this->assertSame('CZ6508000000192000145399', $account->iban);
        $this->assertSame('19-2000145399', $account->number);
        $this->assertSame('CZ65 0800 0000 1920 0014 5399', $account->formattedIban());
    }

    #[Test]
    public function a_czech_iban_gets_its_local_number_and_country(): void
    {
        $account = BankAccount::fromParts(
            'cz15 2010 0000 0020 0123 4561',
            null,
            null,
            null,
            'fiobczpp',
        );

        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame('2001234561', $account->number);
        $this->assertSame('2010', $account->bankCode);
        $this->assertSame(Country::CZ, $account->country);
        $this->assertSame('FIOBCZPP', $account->swift);
    }

    #[Test]
    public function a_slovak_local_number_gets_its_iban(): void
    {
        $account = BankAccount::fromParts(
            null,
            '2000145399',
            '0900',
            Country::SK,
            null,
        );

        $this->assertSame('SK0209000000002000145399', $account->iban);
    }

    #[Test]
    public function an_iban_and_a_matching_local_number_are_accepted_together(): void
    {
        $account = BankAccount::fromParts(
            'CZ6508000000192000145399',
            '000019-2000145399',
            '0800',
            Country::CZ,
            null,
        );

        $this->assertSame('19-2000145399', $account->number);
    }

    #[Test]
    public function another_country_takes_the_iban_only(): void
    {
        $account = BankAccount::fromParts(
            'DE89 3704 0044 0532 0130 00',
            null,
            null,
            null,
            null,
        );

        $this->assertSame(Country::DE, $account->country);
        $this->assertNull($account->number);
        $this->assertNull($account->bankCode);
    }

    /** @return iterable<string, array{string}> */
    public static function foreignIbans(): iterable
    {
        yield 'Austria' => ['AT611904300234573201'];
        yield 'Germany' => ['DE89370400440532013000'];
        yield 'United Kingdom' => ['GB29NWBK60161331926819'];
        yield 'France' => ['FR1420041010050500013M02606'];
        yield 'Switzerland' => ['CH9300762011623852957'];
        yield 'Italy' => ['IT60X0542811101000000123456'];
        yield 'Netherlands' => ['NL91ABNA0417164300'];
        yield 'Norway' => ['NO9386011117947'];
        yield 'Poland' => ['PL61109010140000071219812874'];
        yield 'Saudi Arabia' => ['SA0380000000608010167519'];
        yield 'Mauritius' => ['MU17BOMM0101101030300200000MUR'];
        yield 'Brazil' => ['BR9700360305000010009795493P1'];
    }

    #[Test]
    #[DataProvider('foreignIbans')]
    public function national_formats_accept_valid_foreign_accounts(string $iban): void
    {
        $account = BankAccount::fromParts(
            $iban,
            null,
            null,
            null,
            null,
        );
        $this->assertSame($iban, $account->iban);
    }

    /** @return iterable<string, array{Country, string}> */
    public static function malformedIbans(): iterable
    {
        yield 'short-german-iban' => [Country::DE, '12345678901'];
        yield 'long-german-iban' => [Country::DE, '3704004405320130000'];
        yield 'german-letter' => [Country::DE, 'A70400440532013000'];
        yield 'american-iban' => [Country::US, '12345678901'];
        yield 'british-numeric-bank' => [Country::GB, '123460161331926819'];
        foreach ([Country::CZ, Country::SK] as $country) {
            yield $country->value . '-extra-digits' => [$country, '2010000000200123456100'];
            yield $country->value . '-missing-digit' => [$country, '2010000000200123456'];
            yield $country->value . '-non-numeric-bank-code' => [$country, '20A00000002001234561'];
        }
    }

    #[Test]
    #[DataProvider('malformedIbans')]
    public function a_valid_checksum_does_not_allow_a_malformed_iban(Country $country, string $bban): void
    {
        $digits = $bban . $country->value . '00';
        $remainder = 0;
        foreach (str_split($digits) as $character) {
            $value = ctype_digit($character) ? $character : (string) (ord($character) - 55);
            foreach (str_split($value) as $digit) {
                $remainder = ($remainder * 10 + (int) $digit) % 97;
            }
        }
        $iban = $country->value . str_pad(
            (string) (98 - $remainder),
            2,
            '0',
            STR_PAD_LEFT,
        ) . $bban;
        $this->expectException(InvalidBankAccountException::class);

        BankAccount::fromParts(
            $iban,
            null,
            null,
            null,
            null,
        );
    }

    /**
     * @return iterable<string, array{string|null, string|null, string|null, Country|null, string|null, BankAccountProblem}>
     */
    public static function invalidAccounts(): iterable
    {
        yield 'nothing but a swift' => [
            null,
            null,
            null,
            null,
            'FIOBCZPP',
            BankAccountProblem::MISSING_DETAILS,
        ];
        yield 'number without bank code' => [
            null,
            '2001234561',
            null,
            Country::CZ,
            null,
            BankAccountProblem::MISSING_DETAILS,
        ];
        yield 'number without country' => [
            null,
            '2001234561',
            '2010',
            null,
            null,
            BankAccountProblem::MISSING_DETAILS,
        ];
        yield 'wrong iban checksum' => [
            'CZ1620100000002001234561',
            null,
            null,
            null,
            null,
            BankAccountProblem::INVALID_IBAN,
        ];
        yield 'iban of no country' => [
            'XX1520100000002001234561',
            null,
            null,
            null,
            null,
            BankAccountProblem::INVALID_IBAN,
        ];
        yield 'wrong local checksum' => [
            null,
            '2001234562',
            '2010',
            Country::CZ,
            null,
            BankAccountProblem::INVALID_NUMBER,
        ];
        yield 'bank code of three digits' => [
            null,
            '2001234561',
            '201',
            Country::CZ,
            null,
            BankAccountProblem::INVALID_BANK_CODE,
        ];
        yield 'local number abroad' => [
            null,
            '2001234561',
            '2010',
            Country::DE,
            null,
            BankAccountProblem::LOCAL_NUMBER_NOT_SUPPORTED,
        ];
        yield 'iban of another country than given' => [
            'DE89370400440532013000',
            null,
            null,
            Country::CZ,
            null,
            BankAccountProblem::IBAN_MISMATCH,
        ];
        yield 'iban and another number' => [
            'CZ1520100000002001234561',
            '1234567899',
            '0100',
            null,
            null,
            BankAccountProblem::IBAN_MISMATCH,
        ];
        yield 'malformed swift' => [
            'CZ1520100000002001234561',
            null,
            null,
            null,
            'FIO',
            BankAccountProblem::INVALID_SWIFT,
        ];
    }

    #[Test]
    #[DataProvider('invalidAccounts')]
    public function incomplete_or_inconsistent_details_are_refused(
        ?string $iban,
        ?string $number,
        ?string $bankCode,
        ?Country $country,
        ?string $swift,
        BankAccountProblem $problem,
    ): void {
        try {
            BankAccount::fromParts(
                $iban,
                $number,
                $bankCode,
                $country,
                $swift,
            );
        } catch (InvalidBankAccountException $e) {
            $this->assertSame($problem, $e->problem);

            return;
        }

        $this->fail('The account was accepted');
    }
}
