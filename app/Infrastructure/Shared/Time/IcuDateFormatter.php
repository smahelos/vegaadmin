<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Time;

use App\Domain\Shared\Time\Contracts\DateFormatterInterface;
use DateTimeImmutable;
use IntlDateFormatter;
use RuntimeException;

/**
 * Medium length formats from ICU (cs "5. 3. 2026", de "05.03.2026", en "Mar 5, 2026"):
 * numeric where the language writes dates numerically, always with a four-digit year.
 */
final class IcuDateFormatter implements DateFormatterInterface
{
    public function formatDate(DateTimeImmutable $date, string $locale): string
    {
        return $this->format(
            $date,
            $locale,
            IntlDateFormatter::NONE,
        );
    }

    public function formatDateTime(DateTimeImmutable $dateTime, string $locale): string
    {
        return $this->format(
            $dateTime,
            $locale,
            IntlDateFormatter::SHORT,
        );
    }

    private function format(
        DateTimeImmutable $date,
        string $locale,
        int $timeType,
    ): string {
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            $timeType,
            $date->getTimezone(),
        );
        $formatted = $formatter->format($date);
        if ($formatted === false) {
            throw new RuntimeException("Date formatting failed for locale {$locale}: " . $formatter->getErrorMessage());
        }

        return $formatted;
    }
}
