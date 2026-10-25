<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Shared\Time;

use App\Infrastructure\Shared\Time\IcuDateFormatter;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IcuDateFormatterTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function locales(): array
    {
        return [
            'cs' => [
                'cs',
                '5. 3. 2026',
                '5. 3. 2026 14:07',
            ],
            'de' => [
                'de',
                '05.03.2026',
                '05.03.2026, 14:07',
            ],
            'en' => [
                'en',
                'Mar 5, 2026',
                "Mar 5, 2026, 2:07\u{202F}PM",
            ],
            'sk' => [
                'sk',
                '5. 3. 2026',
                '5. 3. 2026, 14:07',
            ],
        ];
    }

    #[Test]
    #[DataProvider('locales')]
    public function formats_dates_in_the_convention_of_the_language(
        string $locale,
        string $date,
        string $dateTime,
    ): void {
        $formatter = new IcuDateFormatter;
        $value = new DateTimeImmutable('2026-03-05 14:07', new DateTimeZone('Europe/Prague'));

        $this->assertSame($date, $formatter->formatDate($value, $locale));
        $this->assertSame($dateTime, $formatter->formatDateTime($value, $locale));
    }

    #[Test]
    public function keeps_the_timezone_of_the_date(): void
    {
        $formatter = new IcuDateFormatter;
        $lateEvening = new DateTimeImmutable('2026-03-05 23:30', new DateTimeZone('Pacific/Honolulu'));

        $this->assertSame('5. 3. 2026', $formatter->formatDate($lateEvening, 'cs'));
    }
}
