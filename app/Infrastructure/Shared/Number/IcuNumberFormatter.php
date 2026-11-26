<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Number;

use App\Domain\Shared\Number\Contracts\NumberFormatterInterface;
use NumberFormatter;
use RuntimeException;

/**
 * ICU decimal format with grouping and at most three decimals, trailing zeros dropped (cs "1 234,5").
 */
final class IcuNumberFormatter implements NumberFormatterInterface
{
    private const int MAX_FRACTION_DIGITS = 3;

    public function format(string $decimal, string $locale): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, self::MAX_FRACTION_DIGITS);
        // A float is exact enough for display with three decimals; nothing is computed with it
        $formatted = $formatter->format((float) $decimal);
        if ($formatted === false) {
            throw new RuntimeException(
                "Number formatting failed for locale {$locale}: " . $formatter->getErrorMessage(),
            );
        }

        return $formatted;
    }
}
