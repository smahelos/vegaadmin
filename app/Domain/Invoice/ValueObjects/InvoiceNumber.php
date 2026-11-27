<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice Number Value Object
 *
 * Format: four-digit year followed by the yearly sequence, zero-padded to at least
 * four digits (20250001 … 20259999, then 202510000 …). The sequence is capped at
 * 999999 so the number still fits the 10-digit variable symbol of QR payments.
 */
final readonly class InvoiceNumber
{
    private const MAX_SEQUENCE = 999999;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        // Check format: YYYY + 4 to 6 digit sequence
        if (
            !preg_match(
                '/^(\d{4})(\d{4,6})$/',
                $value,
                $matches,
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid invoice number format. Expected YYYYXXXX, got: {$value}",
            );
        }

        $year = (int) $matches[1];
        $sequence = (int) $matches[2];

        if ($year < 2000 || $year > 9999) {
            throw new InvalidArgumentException("Invalid year in invoice number: {$year}");
        }

        if ($sequence < 1 || $sequence > self::MAX_SEQUENCE) {
            throw new InvalidArgumentException("Invalid sequence in invoice number: {$sequence}");
        }

        // Only the canonical padding is valid (2025010000 would be a second spelling of 202510000)
        if ($value !== self::format($year, $sequence)) {
            throw new InvalidArgumentException(
                "Invalid invoice number format. Expected YYYYXXXX, got: {$value}",
            );
        }

        $this->value = $value;
    }

    /**
     * Create invoice number from year and sequence
     */
    public static function fromYearAndSequence(int $year, int $sequence): self
    {
        if ($year < 2000 || $year > 9999) {
            throw new InvalidArgumentException("Year must be between 2000 and 9999, got: {$year}");
        }

        if ($sequence < 1 || $sequence > self::MAX_SEQUENCE) {
            throw new InvalidArgumentException(
                'Sequence must be between 1 and ' . self::MAX_SEQUENCE . ", got: {$sequence}",
            );
        }

        return new self(
            self::format($year, $sequence),
        );
    }

    /**
     * The number after the highest one of the year's series, or its first number.
     *
     * @param  string|null  $lastNumber  The highest number of the series of $year
     */
    public static function generateNext(int $year, ?string $lastNumber): self
    {
        if ($lastNumber === null) {
            return self::fromYearAndSequence($year, 1);
        }

        $last = new self($lastNumber);
        if ($last->year() !== $year) {
            throw new InvalidArgumentException(
                "Invoice number {$lastNumber} is not of the series of {$year}",
            );
        }
        if ($last->sequence() >= self::MAX_SEQUENCE) {
            throw new InvalidArgumentException("Invoice number sequence for year {$year} is exhausted");
        }

        return self::fromYearAndSequence($year, $last->sequence() + 1);
    }

    public function year(): int
    {
        return (int) substr(
            $this->value,
            0,
            4,
        );
    }

    public function sequence(): int
    {
        return (int) substr($this->value, 4);
    }

    public function isGreaterThan(InvoiceNumber $other): bool
    {
        if ($this->year() !== $other->year()) {
            return $this->year() > $other->year();
        }

        return $this->sequence() > $other->sequence();
    }

    private static function format(int $year, int $sequence): string
    {
        return $year . sprintf('%04d', $sequence);
    }
}
