<?php

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice Number Value Object
 * 
 * Handles invoice number generation, validation and formatting
 */
class InvoiceNumber
{
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

        if ($sequence < 1 || $sequence > 9999) {
            throw new InvalidArgumentException("Sequence must be between 1 and 9999, got: {$sequence}");
        }

        $number = $year . sprintf('%04d', $sequence);
        return new self($number);
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

        // Otherwise increment sequence
        return self::fromYearAndSequence($currentYear, $lastInvoice->getSequence() + 1);
    }

    /**
     * Validate and parse invoice number
     */
    private function validateAndParse(string $number): void
    {
        // Remove any whitespace
        $number = trim($number);

        // Check format: YYYYXXXX (year + 4-digit sequence)
        if (!preg_match('/^(\d{4})(\d{4})$/', $number, $matches)) {
            throw new InvalidArgumentException("Invalid invoice number format. Expected YYYYXXXX, got: {$number}");
        }

        $this->year = (int) $matches[1];
        $this->sequence = (int) $matches[2];
        $this->number = $number;

        // Validate year
        if ($this->year < 2000 || $this->year > 9999) {
            throw new InvalidArgumentException("Invalid year in invoice number: {$this->year}");
        }

        // Validate sequence (0001-9999)
        if ($this->sequence < 1 || $this->sequence > 9999) {
            throw new InvalidArgumentException("Invalid sequence in invoice number: {$this->sequence}");
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
