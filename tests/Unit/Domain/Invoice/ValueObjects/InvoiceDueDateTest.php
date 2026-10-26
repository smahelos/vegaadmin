<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceDueDateTest extends TestCase
{
    #[Test]
    public function adds_the_days_to_the_issue_date(): void
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), 30);

        $this->assertSame('2024-01-31', $dueDate->date()->format('Y-m-d'));
    }

    #[Test]
    public function is_due_on_the_issue_date_with_zero_days(): void
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), 0);

        $this->assertSame('2024-01-01', $dueDate->date()->format('Y-m-d'));
    }

    #[Test]
    public function accepts_the_maximum_days(): void
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), InvoiceDueDate::MAX_DAYS);

        $this->assertSame('2024-12-31', $dueDate->date()->format('Y-m-d'));
    }

    #[Test]
    public function ignores_the_time_of_the_issue_date(): void
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01 23:30'), 1);

        $this->assertSame('2024-01-02 00:00', $dueDate->date()->format('Y-m-d H:i'));
    }

    #[Test]
    public function rejects_negative_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Due days must be between 0 and 365, got: -5');

        InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), -5);
    }

    #[Test]
    public function rejects_more_than_the_maximum_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Due days must be between 0 and 365, got: 366');

        InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), 366);
    }

    #[Test]
    public function counts_calendar_days_until_the_due_date(): void
    {
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-01-01'), 30);

        $this->assertSame(10, $dueDate->daysUntil(new DateTimeImmutable('2024-01-21 18:45')));
        $this->assertSame(0, $dueDate->daysUntil(new DateTimeImmutable('2024-01-31 23:59')));
        $this->assertSame(-5, $dueDate->daysUntil(new DateTimeImmutable('2024-02-05 00:01')));
    }

    #[Test]
    public function counts_days_across_a_daylight_saving_change(): void
    {
        $prague = new DateTimeZone('Europe/Prague');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays(new DateTimeImmutable('2024-03-25', $prague), 14);

        $this->assertSame('2024-04-08', $dueDate->date()->format('Y-m-d'));
        $this->assertSame(14, $dueDate->daysUntil(new DateTimeImmutable('2024-03-25 12:00', $prague)));
    }
}
