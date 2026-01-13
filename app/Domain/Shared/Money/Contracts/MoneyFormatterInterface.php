<?php

namespace App\Domain\Shared\Money\Contracts;

use App\Domain\Shared\Money\ValueObjects\Money;

/**
 * Money formatter contract.
 * Formats Money value objects into locale-aware human readable strings.
 */
interface MoneyFormatterInterface
{
    /**
     * Format money using currency symbol when available (fallback to currency code).
     *
     * @param Money $money Money value object
     * @param string|null $locale Locale like cs_CZ, en_US (optional)
     * @param int|null $minFraction Minimum fraction digits to display (null = default 2)
     * @param int|null $maxFraction Maximum fraction digits to display (null = default 2, max 8)
     * @return string Formatted amount
     */
    public function format(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string;

    /**
     * Format money but always use currency code (e.g. 1 234,50 CZK), ignoring symbol preference.
     * @param Money $money
     * @param string|null $locale
     * @param int|null $minFraction
     * @param int|null $maxFraction
     */
    public function formatWithCode(Money $money, ?string $locale = null, ?int $minFraction = null, ?int $maxFraction = null): string;
}
