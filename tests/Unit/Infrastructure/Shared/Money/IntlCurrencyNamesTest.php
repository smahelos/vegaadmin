<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Shared\Money;

use App\Domain\Shared\Money\Enums\Currency;
use App\Infrastructure\Shared\Money\IntlCurrencyNames;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IntlCurrencyNamesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'cs' => [
                'cs',
                'česká koruna',
            ],
            'de' => [
                'de',
                'Tschechische Krone',
            ],
            'en' => [
                'en',
                'Czech Koruna',
            ],
            'sk' => [
                'sk',
                'česká koruna',
            ],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function names_every_currency_in_the_locale(string $locale, string $czk): void
    {
        $names = new IntlCurrencyNames;

        $this->assertSame(
            $czk,
            $names->name(Currency::CZK, $locale),
        );
        foreach (Currency::cases() as $currency) {
            $this->assertNotSame(
                $currency->value,
                $names->name($currency, $locale),
            );
        }
    }
}
