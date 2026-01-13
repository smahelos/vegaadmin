<?php

namespace App\Domain\Invoice\ValueObjects;

use InvalidArgumentException;

/**
 * Invoice Due Date Value Object
 * 
 * Handles invoice due date calculations and validations
 */
class InvoiceDueDate
{
    private \DateTimeImmutable $dueDate;
    private \DateTimeImmutable $issueDate;
    private int $dueDays;

    public function __construct(\DateTimeImmutable $issueDate, int $dueDays)
    {
        $this->validateDueDays($dueDays);
        
        $this->issueDate = $issueDate;
        $this->dueDays = $dueDays;
        $this->dueDate = $issueDate->add(new \DateInterval("P{$dueDays}D"));
    }

    /**
     * Create from issue date and due days
     */
    public static function fromIssueDateAndDays(\DateTimeImmutable $issueDate, int $dueDays): self
    {
        return new self($issueDate, $dueDays);
    }

    /**
     * Create from current date and due days
     */
    public static function fromCurrentDateAndDays(int $dueDays): self
    {
        return new self(new \DateTimeImmutable(), $dueDays);
    }

    /**
     * Create from specific due date
     */
    public static function fromDueDate(\DateTimeImmutable $dueDate, ?\DateTimeImmutable $issueDate = null): self
    {
        $issueDate = $issueDate ?? new \DateTimeImmutable();
        
        if ($dueDate < $issueDate) {
            throw new InvalidArgumentException('Due date cannot be before issue date');
        }

        $dueDays = $issueDate->diff($dueDate)->days;
        return new self($issueDate, $dueDays);
    }

    /**
     * Validate due days
     */
    private function validateDueDays(int $dueDays): void
    {
        if ($dueDays < 0) {
            throw new InvalidArgumentException("Due days cannot be negative, got: {$dueDays}");
        }

        if ($dueDays > 365) {
            throw new InvalidArgumentException("Due days cannot exceed 365 days, got: {$dueDays}");
        }
    }

    /**
     * Get due date
     */
    public function getDueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    /**
     * Get issue date
     */
    public function getIssueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    /**
     * Get due days
     */
    public function getDueDays(): int
    {
        return $this->dueDays;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(?\DateTimeImmutable $currentDate = null): bool
    {
        $currentDate = $currentDate ?? new \DateTimeImmutable();
        return $currentDate > $this->dueDate;
    }

    /**
     * Get days until due (negative if overdue)
     */
    public function getDaysUntilDue(?\DateTimeImmutable $currentDate = null): int
    {
        $currentDate = $currentDate ?? new \DateTimeImmutable();
        $diff = $currentDate->diff($this->dueDate);
        
        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Check if due soon (within specified days)
     */
    public function isDueSoon(int $withinDays = 7, ?\DateTimeImmutable $currentDate = null): bool
    {
        $daysUntilDue = $this->getDaysUntilDue($currentDate);
        return $daysUntilDue >= 0 && $daysUntilDue <= $withinDays;
    }

    /**
     * Format due date for display
     */
    public function formatDueDate(string $format = 'Y-m-d'): string
    {
        return $this->dueDate->format($format);
    }

    /**
     * Format issue date for display
     */
    public function formatIssueDate(string $format = 'Y-m-d'): string
    {
        return $this->issueDate->format($format);
    }

    /**
     * Get status description
     */
    public function getStatusDescription(?\DateTimeImmutable $currentDate = null): string
    {
        if ($this->isOverdue($currentDate)) {
            $daysOverdue = abs($this->getDaysUntilDue($currentDate));
            return "Overdue by {$daysOverdue} days";
        }

        if ($this->isDueSoon(7, $currentDate)) {
            $daysUntilDue = $this->getDaysUntilDue($currentDate);
            return "Due in {$daysUntilDue} days";
        }

        $daysUntilDue = $this->getDaysUntilDue($currentDate);
        return "Due in {$daysUntilDue} days";
    }

    /**
     * Extend due date by additional days
     */
    public function extend(int $additionalDays): self
    {
        if ($additionalDays <= 0) {
            throw new InvalidArgumentException("Additional days must be positive, got: {$additionalDays}");
        }

        return new self($this->issueDate, $this->dueDays + $additionalDays);
    }

    /**
     * Convert to string (due date in Y-m-d format)
     */
    public function __toString(): string
    {
        return $this->formatDueDate();
    }

    /**
     * Check equality
     */
    public function equals(InvoiceDueDate $other): bool
    {
        return $this->dueDate == $other->dueDate && $this->issueDate == $other->issueDate;
    }
}
