<?php

declare(strict_types=1);

namespace App\Domain\Invoice\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Due date of an invoice: the issue date plus the number of days the client has to pay.
 * Works with calendar days; the time of day of the given dates is ignored.
 */
final readonly class InvoiceDueDate
{
    public const MAX_DAYS = 365;

    private function __construct(
        public DateTimeImmutable $issueDate,
        public int $days,
    ) {}

    /**
     * @throws InvalidArgumentException when the days are negative or exceed MAX_DAYS
     */
    public static function fromIssueDateAndDays(DateTimeImmutable $issueDate, int $days): self
    {
        if ($days < 0 || $days > self::MAX_DAYS) {
            throw new InvalidArgumentException('Due days must be between 0 and ' . self::MAX_DAYS . ", got: {$days}");
        }

        return new self($issueDate->setTime(0, 0), $days);
    }

    public function date(): DateTimeImmutable
    {
        return $this->issueDate->modify("+{$this->days} days");
    }

    /**
     * Calendar days from the given day to the due date: 0 on the due date, negative after it.
     */
    public function daysUntil(DateTimeImmutable $day): int
    {
        return (int) $day->setTime(0, 0)->diff($this->date())->format('%r%a');
    }
}
