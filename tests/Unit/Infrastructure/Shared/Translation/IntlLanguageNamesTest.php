<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Shared\Translation;

use App\Domain\Shared\Translation\Enums\Language;
use App\Infrastructure\Shared\Translation\IntlLanguageNames;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IntlLanguageNamesTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'cs' => [
                'cs',
                'Čeština',
            ],
            'de' => [
                'de',
                'Tschechisch',
            ],
            'en' => [
                'en',
                'Czech',
            ],
            'sk' => [
                'sk',
                'Čeština',
            ],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function names_every_language_in_the_locale_with_a_capital(string $locale, string $czech): void
    {
        $names = new IntlLanguageNames;

        $this->assertSame(
            $czech,
            $names->name(Language::CS, $locale),
        );
        foreach (Language::cases() as $language) {
            $name = $names->name($language, $locale);
            $this->assertNotSame($language->value, $name);
            $this->assertSame(
                mb_strtoupper(
                    mb_substr(
                        $name,
                        0,
                        1,
                    ),
                ),
                mb_substr(
                    $name,
                    0,
                    1,
                ),
            );
        }
    }
}
