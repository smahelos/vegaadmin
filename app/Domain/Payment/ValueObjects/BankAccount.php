<?php

declare(strict_types=1);

namespace App\Domain\Payment\ValueObjects;

use App\Domain\Payment\Enums\BankAccountProblem;
use App\Domain\Payment\Exceptions\InvalidBankAccountException;
use App\Domain\Shared\Geography\Enums\Country;

/**
 * Payment details of a bank account. The IBAN is always known; Czech and Slovak accounts also carry the local
 * number and bank code, computed from the IBAN when only the IBAN was given and the other way round.
 * The checks confirm the notation only, not that the account exists.
 */
final readonly class BankAccount
{
    /**
     * Countries whose local notation (prefix-number/bank code, weighted mod 11) is understood.
     */
    private const LOCAL_NUMBER_COUNTRIES = [Country::CZ, Country::SK];

    private const PREFIX_WEIGHTS = [
        10,
        5,
        8,
        4,
        2,
        1,
    ];

    private const NUMBER_WEIGHTS = [
        6,
        3,
        7,
        9,
        10,
        5,
        8,
        4,
        2,
        1,
    ];

    private function __construct(
        public string $iban,
        public ?string $number,
        public ?string $bankCode,
        public Country $country,
        public ?string $swift,
    ) {}

    /**
     * Takes an IBAN, a local number with its bank code and country, or both; given together they must match.
     *
     * @throws InvalidBankAccountException
     */
    public static function fromParts(
        ?string $iban,
        ?string $number,
        ?string $bankCode,
        ?Country $country,
        ?string $swift,
    ): self {
        $iban = self::compact($iban);
        $number = self::compact($number);
        $bankCode = self::compact($bankCode);
        $swift = self::compact($swift);

        if ($swift !== null && preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $swift) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_SWIFT);
        }

        if ($iban === null) {
            return self::fromLocalNumber(
                $number,
                $bankCode,
                $country,
                $swift,
            );
        }

        $ibanCountry = self::assertIban($iban);
        if ($country !== null && $country !== $ibanCountry) {
            throw new InvalidBankAccountException(BankAccountProblem::IBAN_MISMATCH);
        }

        if (! in_array(
            $ibanCountry,
            self::LOCAL_NUMBER_COUNTRIES,
            true,
        )) {
            if ($number !== null || $bankCode !== null) {
                throw new InvalidBankAccountException(BankAccountProblem::LOCAL_NUMBER_NOT_SUPPORTED);
            }

            return new self(
                $iban,
                null,
                null,
                $ibanCountry,
                $swift,
            );
        }

        $fromIban = self::fromLocalNumber(
            self::localNumberInIban($iban),
            substr(
                $iban,
                4,
                4,
            ),
            $ibanCountry,
            $swift,
        );
        if ($fromIban->iban !== $iban) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_IBAN);
        }

        if ($number !== null || $bankCode !== null) {
            $given = self::fromLocalNumber(
                $number,
                $bankCode,
                $ibanCountry,
                $swift,
            );

            if ($given->iban !== $fromIban->iban) {
                throw new InvalidBankAccountException(BankAccountProblem::IBAN_MISMATCH);
            }
        }

        return $fromIban;
    }

    /**
     * The IBAN in groups of four characters, the way it is printed.
     */
    public function formattedIban(): string
    {
        return implode(' ', str_split($this->iban, 4));
    }

    private static function fromLocalNumber(
        ?string $number,
        ?string $bankCode,
        ?Country $country,
        ?string $swift,
    ): self {
        if ($number === null || $bankCode === null || $country === null) {
            throw new InvalidBankAccountException(BankAccountProblem::MISSING_DETAILS);
        }

        if (! in_array(
            $country,
            self::LOCAL_NUMBER_COUNTRIES,
            true,
        )) {
            throw new InvalidBankAccountException(BankAccountProblem::LOCAL_NUMBER_NOT_SUPPORTED);
        }

        if (preg_match('/^\d{4}$/', $bankCode) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_BANK_CODE);
        }

        if (preg_match(
            '/^(?:(\d{1,6})-)?(\d{2,10})$/',
            $number,
            $parts,
        ) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_NUMBER);
        }

        $prefix = str_pad(
            $parts[1],
            6,
            '0',
            STR_PAD_LEFT,
        );
        $base = str_pad(
            $parts[2],
            10,
            '0',
            STR_PAD_LEFT,
        );

        if (! self::passesWeightedMod11($prefix, self::PREFIX_WEIGHTS) || ! self::passesWeightedMod11($base, self::NUMBER_WEIGHTS)) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_NUMBER);
        }

        $bban = $bankCode . $prefix . $base;

        return new self(
            $country->value . self::ibanCheckDigits($country, $bban) . $bban,
            self::canonicalNumber($prefix, $base),
            $bankCode,
            $country,
            $swift,
        );
    }

    /**
     * @throws InvalidBankAccountException when the format, country or mod-97 checksum is wrong
     */
    private static function assertIban(string $iban): Country
    {
        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{11,30}$/', $iban) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_IBAN);
        }

        $country = Country::tryFrom(substr($iban, 0, 2));
        if ($country === null || self::mod97(substr($iban, 4) . substr($iban, 0, 4)) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_IBAN);
        }
        if (in_array($country, self::LOCAL_NUMBER_COUNTRIES, true) && preg_match('/^[A-Z]{2}[0-9]{22}$/D', $iban) !== 1) {
            throw new InvalidBankAccountException(BankAccountProblem::INVALID_IBAN);
        }

        return $country;
    }

    /**
     * A Czech or Slovak IBAN holds the bank code, the six-digit prefix and the ten-digit number.
     */
    private static function localNumberInIban(string $iban): string
    {
        return substr(
            $iban,
            8,
            6,
        ) . '-' . substr(
            $iban,
            14,
            10,
        );
    }

    /**
     * The number without padding zeros; a zero prefix is left out.
     */
    private static function canonicalNumber(string $prefix, string $base): string
    {
        $base = ltrim($base, '0');
        $prefix = ltrim($prefix, '0');

        return $prefix === '' ? $base : $prefix . '-' . $base;
    }

    /**
     * @param  list<int>  $weights
     */
    private static function passesWeightedMod11(string $digits, array $weights): bool
    {
        $sum = 0;
        foreach ($weights as $position => $weight) {
            $sum += (int) $digits[$position] * $weight;
        }

        return $sum % 11 === 0;
    }

    private static function ibanCheckDigits(Country $country, string $bban): string
    {
        $remainder = self::mod97($bban . $country->value . '00');

        return str_pad(
            (string) (98 - $remainder),
            2,
            '0',
            STR_PAD_LEFT,
        );
    }

    /**
     * ISO 7064 mod 97 of an alphanumeric string, letters counted as 10–35, computed in chunks to stay within int.
     */
    private static function mod97(string $value): int
    {
        $digits = '';
        foreach (str_split($value) as $character) {
            $digits .= ctype_digit($character) ? $character : (string) (ord($character) - 55);
        }

        $remainder = 0;
        foreach (str_split($digits, 7) as $chunk) {
            $remainder = (int) ($remainder . $chunk) % 97;
        }

        return $remainder;
    }

    /**
     * Spaces are only notation; letters are compared in upper case. Empty is null.
     */
    private static function compact(?string $value): ?string
    {
        $value = strtoupper(preg_replace('/\s+/', '', $value ?? '') ?? '');

        return $value === '' ? null : $value;
    }
}
