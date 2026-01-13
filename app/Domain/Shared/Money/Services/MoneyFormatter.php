<?php

namespace App\Domain\Shared\Money\Services;

use App\Domain\Shared\Money\Contracts\MoneyFormatterInterface;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\User\Contracts\Locale;
use InvalidArgumentException;

/**
 * Locale-aware Money formatter.
 * Uses PHP intl NumberFormatter if available; falls back to manual formatting otherwise.
 */
class MoneyFormatter implements MoneyFormatterInterface
{
    public function __construct(
        private readonly Locale $locale,
    )
    {
    }

    /** Default min fraction digits when not specified. */
    private const DEFAULT_MIN_FRACTION = 2;
    /** Default max fraction digits when not specified. */
    private const DEFAULT_MAX_FRACTION = 2;

    public function format(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
    {
        return $this->formatInternal($money, $locale, $minFraction, $maxFraction, false);
    }

    public function formatWithCode(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string
    {
        return $this->formatInternal($money, $locale, $minFraction, $maxFraction, true);
    }

    /**
     * Internal formatting logic.
     * Rules (per požadavek):
     *  - Zachovat lokální oddělovače (decimal + thousand) dle locale.
     *  - Vždy prefixovat měnu PŘED číslo.
     *  - Pokud je k dispozici krátký jednoznakový symbol (€, $, £, ¥), použít symbol bez mezery: "€48,00".
     *  - V ostatních případech použít kód (CZK, USD, ...) + mezera: "CZK 48,00".
     *  - Metoda formatWithCode() vždy použije kód (před číslem) bez ohledu na dostupnost symbolu.
     */
    private function formatInternal(Money $money, ?string $locale, ?int $minFraction, ?int $maxFraction, bool $forceCode): string
    {
        $locale = $locale ?: $this->locale->getLocale();
        $min = $minFraction ?? self::DEFAULT_MIN_FRACTION;
        $max = $maxFraction ?? self::DEFAULT_MAX_FRACTION;
        if ($min < 0 || $max < 0 || $min > $max || $max > 8) {
            throw new InvalidArgumentException('Invalid fraction digit configuration');
        }

        $currency = strtoupper($money->getCurrency());
        $amountStr = $money->getAmount();

        // Determine scale of underlying amount
        $scale = 0;
        if (str_contains($amountStr, '.')) {
            $scale = strlen(explode('.', $amountStr, 2)[1]);
        }
        // Clamp displayed fraction according to available digits
        $displayMax = min($max, max($scale, $min));

        // Format numeric part locale-aware (WITHOUT currency) using intl if possible
        $numberFormatted = $this->formatNumberPortion($money->toFloat(), $locale, $displayMax);

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

        // Handle sign
        $sign = '';
        if ($money->toFloat() < 0) {
            $sign = '-';
            // strip any leading minus from numeric part just in case
            $numberFormatted = ltrim($numberFormatted, '-');
        }

        // Build final string
        if ($useSymbol) {
            // Symbol directly before amount without space: €1 234,56
            return $sign . $currencyToken . $numberFormatted;
        }
        // Code with space: CZK 1 234,56
        return $sign . $currencyToken . ' ' . $numberFormatted;
    }

    /**
     * Format just the numeric portion with locale separators.
     */
    private function formatNumberPortion(float $value, string $locale, int $fractionDigits): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $nf = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $nf->setAttribute(\NumberFormatter::FRACTION_DIGITS, $fractionDigits);
            $formatted = $nf->format($value);
            if ($formatted !== false) {
                return $formatted;
            }
        }
        // Fallback simple rules
        $decimalSep = str_starts_with($locale, 'cs') ? ',' : '.';
        $thousandSep = str_starts_with($locale, 'cs') ? ' ' : ',';
        return number_format($value, $fractionDigits, $decimalSep, $thousandSep);
    }

    /**
     * Try to obtain currency symbol using intl.
     */
    private function getIntlCurrencySymbol(string $currency, string $locale): ?string
    {
        if (!class_exists(\NumberFormatter::class)) {
            return null;
        }
        try {
            $formatter = new \NumberFormatter($locale . '@currency=' . $currency, \NumberFormatter::CURRENCY);
            // format a dummy value to force internal symbol resolution
            $formatter->formatCurrency(1, $currency);
            $symbol = $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
            return $symbol ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Determine if symbol is short (one grapheme like €, $, £, ¥, ₿ etc.).
     */
    private function isShortSymbol(string $symbol): bool
    {
        // Trim whitespace / NBSP
        $trimmed = trim(str_replace(["\u{00A0}", "\u{202F}"], ' ', $symbol));
        // If after trimming length (mb_strlen) <= 2 treat as short symbol
        return mb_strlen($trimmed, 'UTF-8') <= 2;
    }

    /**
     * Legacy fallback formatting (kept for reference, now unused directly in main path).
     */
    private function fallbackFormat(Money $money, string $locale, int $fractionDigits, bool $forceCode): string
    {
        $decimalSep = str_starts_with($locale, 'cs') ? ',' : '.';
        $thousandSep = str_starts_with($locale, 'cs') ? ' ' : ',';
        $currency = strtoupper($money->getCurrency());
        $numeric = $money->toFloat();
        $formatted = number_format($numeric, $fractionDigits, $decimalSep, $thousandSep);
        // Always prefix code in fallback; symbol detection not available
        if ($numeric < 0) {
            $formatted = ltrim($formatted, '-');
            return '-' . $currency . ' ' . $formatted;
        }
        return $currency . ' ' . $formatted;
    }
}
