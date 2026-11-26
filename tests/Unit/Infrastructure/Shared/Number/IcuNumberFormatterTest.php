<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Shared\Number;

use App\Infrastructure\Shared\Number\IcuNumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IcuNumberFormatterTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function numbers(): array
    {
        return [
            'cs drops trailing zeros' => [
                'cs',
                '2.000',
                '2',
            ],
            'cs decimal comma and grouping' => [
                'cs',
                '1234.500',
                "1\u{A0}234,5",
            ],
            'de' => [
                'de',
                '1234.125',
                '1.234,125',
            ],
            'en' => [
                'en',
                '21.00',
                '21',
            ],
            'sk' => [
                'sk',
                '12.50',
                '12,5',
            ],
        ];
    }

    #[Test]
    #[DataProvider('numbers')]
    public function formats_a_decimal_string_in_the_language(
        string $locale,
        string $decimal,
        string $expected,
    ): void {
        $formatter = new IcuNumberFormatter;

        $this->assertSame(
            $expected,
            $formatter->format($decimal, $locale),
        );
    }
}
