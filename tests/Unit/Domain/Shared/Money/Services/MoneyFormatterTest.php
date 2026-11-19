<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\Services\MoneyFormatter;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MoneyFormatterTest extends TestCase
{
    private const NBSP = "\u{00A0}";

    private MoneyFormatterInterface $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $locale = Mockery::mock(Locale::class);
        $expectation = $locale->shouldReceive('getLocale');
        $expectation->andReturn('cs');
        $this->formatter = new MoneyFormatter($locale);
    }

    /** @return iterable<string, array{string, string, string, string, string}> */
    public static function localeAmounts(): iterable
    {
        yield 'cs CZK' => [
            'cs',
            'CZK',
            '1234567.89',
            'Kč1' . self::NBSP . '234' . self::NBSP . '567,89',
            'CZK 1' . self::NBSP . '234' . self::NBSP . '567,89',
        ];
        yield 'cs EUR zero' => [
            'cs',
            'EUR',
            '0',
            '€0,00',
            'EUR 0,00',
        ];
        yield 'cs USD' => [
            'cs',
            'USD',
            '1234.5',
            'USD 1' . self::NBSP . '234,50',
            'USD 1' . self::NBSP . '234,50',
        ];
        yield 'de CZK' => [
            'de',
            'CZK',
            '1234.5',
            'CZK 1.234,50',
            'CZK 1.234,50',
        ];
        yield 'de USD' => [
            'de',
            'USD',
            '1234567.89',
            '$1.234.567,89',
            'USD 1.234.567,89',
        ];
        yield 'en EUR' => [
            'en',
            'EUR',
            '1234.5',
            '€1,234.50',
            'EUR 1,234.50',
        ];
        yield 'en CZK' => [
            'en',
            'CZK',
            '1234567.89',
            'CZK 1,234,567.89',
            'CZK 1,234,567.89',
        ];
        yield 'sk EUR' => [
            'sk',
            'EUR',
            '1234567.89',
            '€1' . self::NBSP . '234' . self::NBSP . '567,89',
            'EUR 1' . self::NBSP . '234' . self::NBSP . '567,89',
        ];
        yield 'sk CZK' => [
            'sk',
            'CZK',
            '1234.5',
            'CZK 1' . self::NBSP . '234,50',
            'CZK 1' . self::NBSP . '234,50',
        ];
    }

    #[Test]
    #[DataProvider('localeAmounts')]
    public function formats_with_the_separators_and_currency_of_the_locale(
        string $locale,
        string $currency,
        string $amount,
        string $formatted,
        string $withCode,
    ): void {
        $money = Money::fromString($amount, $currency);

        $this->assertSame(
            $formatted,
            $this->formatter->format($money, $locale),
        );
        $this->assertSame(
            $withCode,
            $this->formatter->formatWithCode($money, $locale),
        );
    }

    #[Test]
    public function formats_in_the_current_locale_when_none_is_given(): void
    {
        $money = Money::fromString('1234.5', 'CZK');

        $this->assertSame(
            'Kč1' . self::NBSP . '234,50',
            $this->formatter->format($money),
        );
    }

    /** @return iterable<string, array{string, int, int, string}> */
    public static function fractionBounds(): iterable
    {
        yield 'rounds to the maximum' => [
            '10.123456',
            2,
            4,
            '€10.1235',
        ];
        yield 'rounds to a whole number' => [
            '10.123456',
            0,
            0,
            '€10',
        ];
        yield 'no fraction when the amount has none' => [
            '10',
            0,
            2,
            '€10',
        ];
        yield 'pads to the minimum' => [
            '10.5',
            3,
            3,
            '€10.500',
        ];
        yield 'keeps eight decimals' => [
            '0.00000001',
            2,
            8,
            '€0.00000001',
        ];
        yield 'rounds a half up' => [
            '0.125',
            2,
            2,
            '€0.13',
        ];
        yield 'keeps every digit of a long amount' => [
            '254751707.34370909',
            0,
            8,
            '€254,751,707.34370909',
        ];
    }

    #[Test]
    #[DataProvider('fractionBounds')]
    public function shows_the_digits_between_the_fraction_bounds(
        string $amount,
        int $minFraction,
        int $maxFraction,
        string $formatted,
    ): void {
        $money = Money::fromString($amount, 'EUR');

        $this->assertSame(
            $formatted,
            $this->formatter->format(
                $money,
                'en',
                $minFraction,
                $maxFraction,
            ),
        );
    }

    #[Test]
    public function invalid_fraction_constraints_throw(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $m = Money::fromString('1', 'USD');
        $this->formatter->format(
            $m,
            'en_US',
            5,
            2,
        ); // min > max
    }

    #[Test]
    public function refuses_an_amount_beyond_the_integer_range(): void
    {
        $money = Money::fromString('99999999999999999999.5', 'EUR');

        $this->expectException(InvalidArgumentException::class);

        $this->formatter->format($money, 'en');
    }
}
