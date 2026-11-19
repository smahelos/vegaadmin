<?php

declare(strict_types=1);

namespace App\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use IntlException;
use InvalidArgumentException;
use NumberFormatter;
use RuntimeException;

/**
 * Locale-aware Money formatter on top of the intl NumberFormatter.
 */
final class MoneyFormatter implements MoneyFormatterInterface
{
    public function __construct(
        private readonly Locale $locale,
    ) {}

    /** Default min fraction digits when not specified. */
    private const DEFAULT_MIN_FRACTION = 2;

    /** Default max fraction digits when not specified. */
    private const DEFAULT_MAX_FRACTION = 2;

    public function format(
        Money $money,
        ?string $locale = null,
        ?int $minFraction = null,
        ?int $maxFraction = null,
    ): string {
        return $this->formatInternal(
            $money,
            $locale,
            $minFraction,
            $maxFraction,
            false,
        );
    }

    public function formatWithCode(
        Money $money,
        ?string $locale = null,
        ?int $minFraction = null,
        ?int $maxFraction = null,
    ): string {
        return $this->formatInternal(
            $money,
            $locale,
            $minFraction,
            $maxFraction,
            true,
        );
    }

    /**
     * Number in the separators of the locale, preceded by the currency: a one-character symbol
     * without a space ("€48,00"), otherwise the code and a space ("CZK 48,00"); formatWithCode()
     * always uses the code.
     */
    private function formatInternal(
        Money $money,
        ?string $locale,
        ?int $minFraction,
        ?int $maxFraction,
        bool $forceCode,
    ): string {
        $locale = $locale ?: $this->locale->getLocale();
        $min = $minFraction ?? self::DEFAULT_MIN_FRACTION;
        $max = $maxFraction ?? self::DEFAULT_MAX_FRACTION;
        if ($min < 0 || $max < 0 || $min > $max || $max > 8) {
            throw new InvalidArgumentException('Invalid fraction digit configuration');
        }

        $currency = $money->currency->value;
        $amountStr = $money->amount;

        // Determine scale of underlying amount
        $scale = 0;
        if (str_contains($amountStr, '.')) {
            $scale = strlen(
                explode(
                    '.',
                    $amountStr,
                    2,
                )[1],
            );
        }
        // Clamp displayed fraction according to available digits
        $displayMax = min(
            $max,
            max($scale, $min),
        );

        $numberFormatted = $this->formatNumberPortion(
            $money,
            $locale,
            $displayMax,
        );

        // Decide currency token (symbol or code)
        $currencyToken = $currency; // default to code
        $useSymbol = false;

        if (!$forceCode) {
            $symbol = $this->getIntlCurrencySymbol($currency, $locale);
            // Accept symbol if it is a single UTF-8 grapheme (length <= 3 bytes) and differs from code
            if ($symbol && $symbol !== $currency && $this->isShortSymbol($symbol)) {
                $currencyToken = $symbol;
                $useSymbol = true;
            }
        }

        // Build final string
        if ($useSymbol) {
            // Symbol directly before amount without space: €1 234,56
            return $currencyToken . $numberFormatted;
        }

        // Code with space: CZK 1 234,56
        return $currencyToken . ' ' . $numberFormatted;
    }

    /**
     * The amount rounded half up to the fraction digits, with the separators of the locale.
     */
    private function formatNumberPortion(
        Money $money,
        string $locale,
        int $fractionDigits,
    ): string {
        $rounded = $money->divideRounding(1, $fractionDigits);
        $parts = explode(
            '.',
            $rounded->amount,
            2,
        );
        $formatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
        // Amount columns are decimal(18, 2); a larger amount must not be clamped to PHP_INT_MAX
        $integerPart = filter_var($parts[0], FILTER_VALIDATE_INT);
        if ($integerPart === false) {
            throw new InvalidArgumentException('Amount too large to format: ' . $money->amount);
        }
        $integer = $formatter->format($integerPart);
        if ($integer === false) {
            throw new RuntimeException('Number formatting failed for locale ' . $locale);
        }
        if ($fractionDigits === 0) {
            return $integer;
        }
        $fraction = str_pad(
            $parts[1] ?? '',
            $fractionDigits,
            '0',
            STR_PAD_RIGHT,
        );

        return $integer . $formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL) . $fraction;
    }

    /**
     * Try to obtain currency symbol using intl.
     */
    private function getIntlCurrencySymbol(string $currency, string $locale): ?string
    {
        try {
            $formatter = new NumberFormatter($locale . '@currency=' . $currency, NumberFormatter::CURRENCY);
            // format a dummy value to force internal symbol resolution
            $formatter->formatCurrency(1, $currency);
            $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

            return $symbol ?: null;
        } catch (IntlException) {
            return null;
        }
    }

    /**
     * Determine if symbol is short (one grapheme like €, $, £, ¥, ₿ etc.).
     */
    private function isShortSymbol(string $symbol): bool
    {
        // Trim whitespace / NBSP
        $trimmed = trim(
            str_replace(
                ["\u{00A0}", "\u{202F}"],
                ' ',
                $symbol,
            ),
        );

        // If after trimming length (mb_strlen) <= 2 treat as short symbol
        return mb_strlen($trimmed, 'UTF-8') <= 2;
    }
}
