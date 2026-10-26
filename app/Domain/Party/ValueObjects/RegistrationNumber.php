<?php

declare(strict_types=1);

namespace App\Domain\Party\ValueObjects;

use App\Domain\Party\Enums\RegistrationNumberProblem;
use App\Domain\Party\Exceptions\InvalidRegistrationNumberException;
use App\Domain\Shared\Geography\Enums\Country;

/**
 * A company number together with the country of the register that issued it. Only Czech numbers (IČO)
 * have a known format; numbers of other registers are kept as written.
 */
final readonly class RegistrationNumber
{
    private function __construct(
        public Country $country,
        public string $number,
    ) {}

    /**
     * Null when the number is empty: a country without a number identifies nothing.
     *
     * @throws InvalidRegistrationNumberException
     */
    public static function fromParts(?Country $country, ?string $number): ?self
    {
        $number = trim($number ?? '');
        if ($number === '') {
            return null;
        }

        if ($country === null) {
            throw new InvalidRegistrationNumberException(RegistrationNumberProblem::MISSING_COUNTRY);
        }

        if ($country === Country::CZ) {
            self::assertCzechNumber($number);
        }

        return new self($country, $number);
    }

    public function isCzech(): bool
    {
        return $this->country === Country::CZ;
    }

    /**
     * Eight digits, the last one a mod-11 check digit; missing leading zeros are not added.
     */
    private static function assertCzechNumber(string $number): void
    {
        if (preg_match('/^\d{8}$/', $number) !== 1) {
            throw new InvalidRegistrationNumberException(RegistrationNumberProblem::CZECH_FORMAT);
        }

        $sum = 0;
        for ($position = 0; $position < 7; $position++) {
            $sum += (int) $number[$position] * (8 - $position);
        }

        if ((11 - $sum % 11) % 10 !== (int) $number[7]) {
            throw new InvalidRegistrationNumberException(RegistrationNumberProblem::CZECH_CHECKSUM);
        }
    }
}
