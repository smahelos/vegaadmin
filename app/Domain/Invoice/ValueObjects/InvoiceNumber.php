<?php

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice Number Value Object
 *
 * Format: four-digit year followed by the yearly sequence, zero-padded to at least
 * four digits (20250001 … 20259999, then 202510000 …). The sequence is capped at
 * 999999 so the number still fits the 10-digit variable symbol of QR payments.
 */
class InvoiceNumber
{
    private const MAX_SEQUENCE = 999999;

    private string $number;
    private int $year;
    private int $sequence;

    public function __construct(string $number)
    {
        $this->validateAndParse($number);
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
            throw new InvalidArgumentException('Sequence must be between 1 and ' . self::MAX_SEQUENCE . ", got: {$sequence}");
        }

        return new self(self::format($year, $sequence));
    }

    /**
     * Create next invoice number based on current year
     */
    public static function generateNext(?string $lastNumber = null): self
    {
        $currentYear = (int) date('Y');

        if (!$lastNumber) {
            return self::fromYearAndSequence($currentYear, 1);
        }

        $lastInvoice = new self($lastNumber);

        // If it's a new year, start from 1
        if ($lastInvoice->getYear() < $currentYear) {
            return self::fromYearAndSequence($currentYear, 1);
        }

        if ($lastInvoice->getSequence() >= self::MAX_SEQUENCE) {
            throw new InvalidArgumentException("Invoice number sequence for year {$currentYear} is exhausted");
        }

        // Otherwise increment sequence
        return self::fromYearAndSequence($currentYear, $lastInvoice->getSequence() + 1);
    }

    private static function format(int $year, int $sequence): string
    {
        return $year . sprintf('%04d', $sequence);
    }

    /**
     * Validate and parse invoice number
     */
    private function validateAndParse(string $number): void
    {
        // Remove any whitespace
        $number = trim($number);

        // Check format: YYYY + 4 to 6 digit sequence
        if (!preg_match('/^(\d{4})(\d{4,6})$/', $number, $matches)) {
            throw new InvalidArgumentException("Invalid invoice number format. Expected YYYYXXXX, got: {$number}");
        }

        $this->year = (int) $matches[1];
        $this->sequence = (int) $matches[2];
        $this->number = $number;

        // Validate year
        if ($this->year < 2000 || $this->year > 9999) {
            throw new InvalidArgumentException("Invalid year in invoice number: {$this->year}");
        }

        // Validate sequence (0001-999999)
        if ($this->sequence < 1 || $this->sequence > self::MAX_SEQUENCE) {
            throw new InvalidArgumentException("Invalid sequence in invoice number: {$this->sequence}");
        }

        // Only the canonical padding is valid (2025010000 would be a second spelling of 202510000)
        if ($number !== self::format($this->year, $this->sequence)) {
            throw new InvalidArgumentException("Invalid invoice number format. Expected YYYYXXXX, got: {$number}");
        }
    }

    /**
     * Get the full invoice number
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Get the year part
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get the sequence part
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Format with separator
     */
    public function formatWithSeparator(string $separator = '-'): string
    {
        return $this->year . $separator . sprintf('%04d', $this->sequence);
    }

    /**
     * Check if this number is from current year
     */
    public function isFromCurrentYear(): bool
    {
        return $this->year === (int) date('Y');
    }

    /**
     * Compare with another invoice number
     */
    public function isGreaterThan(InvoiceNumber $other): bool
    {
        if ($this->year !== $other->year) {
            return $this->year > $other->year;
        }

        return $this->sequence > $other->sequence;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->number;
    }

    /**
     * Check equality
     */
    public function equals(InvoiceNumber $other): bool
    {
        return $this->number === $other->getNumber();
    }
}
