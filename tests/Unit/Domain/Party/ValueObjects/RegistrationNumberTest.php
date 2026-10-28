<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Party\ValueObjects;

use App\Domain\Party\Enums\RegistrationNumberProblem;
use App\Domain\Party\Exceptions\InvalidRegistrationNumberException;
use App\Domain\Party\ValueObjects\RegistrationNumber;
use App\Domain\Shared\Geography\Enums\Country;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegistrationNumberTest extends TestCase
{
    #[Test]
    public function a_czech_number_with_a_valid_checksum_is_kept_with_its_leading_zeros(): void
    {
        $number = RegistrationNumber::fromParts(Country::CZ, ' 00006947 ');

        $this->assertSame('00006947', $number?->number);
        $this->assertTrue($number?->isCzech());
    }

    /**
     * @return iterable<string, array{string, RegistrationNumberProblem}>
     */
    public static function invalidCzechNumbers(): iterable
    {
        yield 'missing leading zeros are not added' => ['6947', RegistrationNumberProblem::CZECH_FORMAT];
        yield 'letters' => ['2700012A', RegistrationNumberProblem::CZECH_FORMAT];
        yield 'nine digits' => ['270001234', RegistrationNumberProblem::CZECH_FORMAT];
        yield 'wrong check digit' => ['27074359', RegistrationNumberProblem::CZECH_CHECKSUM];
    }

    #[Test]
    #[DataProvider('invalidCzechNumbers')]
    public function a_czech_number_needs_eight_digits_and_a_valid_checksum(string $number, RegistrationNumberProblem $problem): void
    {
        try {
            RegistrationNumber::fromParts(Country::CZ, $number);
        } catch (InvalidRegistrationNumberException $e) {
            $this->assertSame($problem, $e->problem);

            return;
        }

        $this->fail('The number was accepted');
    }

    #[Test]
    public function a_foreign_number_is_only_trimmed(): void
    {
        $number = RegistrationNumber::fromParts(Country::AT, ' FN 123456 x ');

        $this->assertSame('FN 123456 x', $number?->number);
        $this->assertFalse($number?->isCzech());
    }

    #[Test]
    public function a_number_needs_the_country_of_its_register(): void
    {
        $this->expectExceptionObject(new InvalidRegistrationNumberException(RegistrationNumberProblem::MISSING_COUNTRY));

        RegistrationNumber::fromParts(null, '27074358');
    }

    #[Test]
    public function an_empty_number_is_no_registration_even_with_a_country(): void
    {
        $this->assertNull(RegistrationNumber::fromParts(Country::CZ, '  '));
        $this->assertNull(RegistrationNumber::fromParts(null, null));
    }

    #[Test]
    public function numbers_are_equal_only_from_the_same_register(): void
    {
        $czech = RegistrationNumber::fromParts(Country::CZ, '27074358');

        $this->assertTrue($czech?->equals(RegistrationNumber::fromParts(Country::CZ, ' 27074358 ') ?? $this->fail('Empty number')));
        $this->assertFalse($czech?->equals(RegistrationNumber::fromParts(Country::SK, '27074358') ?? $this->fail('Empty number')));
    }
}
