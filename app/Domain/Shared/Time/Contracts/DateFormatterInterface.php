<?php

declare(strict_types=1);

namespace App\Domain\Shared\Time\Contracts;

use DateTimeImmutable;

/**
 * Formats dates for people in the conventions of a language, in the timezone the date carries.
 */
interface DateFormatterInterface
{
    public function formatDate(DateTimeImmutable $date, string $locale): string;

    public function formatDateTime(DateTimeImmutable $dateTime, string $locale): string;
}
