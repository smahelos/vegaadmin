<?php

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class InvoiceDueDateTest extends TestCase
{
    #[Test]
    public function creates_from_issue_date_and_days(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDays = 30;
        
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, $dueDays);
        
        $this->assertInstanceOf(InvoiceDueDate::class, $dueDate);
        $this->assertEquals($issueDate, $dueDate->getIssueDate());
        $this->assertEquals($dueDays, $dueDate->getDueDays());
        $this->assertEquals('2024-01-31', $dueDate->formatDueDate());
    }

    #[Test]
    public function creates_from_current_date_and_days(): void
    {
        $dueDays = 15;
        
        $dueDate = InvoiceDueDate::fromCurrentDateAndDays($dueDays);
        
        $this->assertInstanceOf(InvoiceDueDate::class, $dueDate);
        $this->assertEquals($dueDays, $dueDate->getDueDays());
    }

    #[Test]
    public function creates_from_specific_due_date(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $specificDueDate = new \DateTimeImmutable('2024-01-31');
        
        $dueDate = InvoiceDueDate::fromDueDate($specificDueDate, $issueDate);
        
        $this->assertInstanceOf(InvoiceDueDate::class, $dueDate);
        $this->assertEquals($issueDate, $dueDate->getIssueDate());
        $this->assertEquals($specificDueDate, $dueDate->getDueDate());
        $this->assertEquals(30, $dueDate->getDueDays());
    }

    #[Test]
    public function throws_exception_for_negative_due_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Due days cannot be negative, got: -5');
        
        $issueDate = new \DateTimeImmutable('2024-01-01');
        InvoiceDueDate::fromIssueDateAndDays($issueDate, -5);
    }

    #[Test]
    public function throws_exception_for_excessive_due_days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Due days cannot exceed 365 days, got: 400');
        
        $issueDate = new \DateTimeImmutable('2024-01-01');
        InvoiceDueDate::fromIssueDateAndDays($issueDate, 400);
    }

    #[Test]
    public function throws_exception_when_due_date_before_issue_date(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Due date cannot be before issue date');
        
        $issueDate = new \DateTimeImmutable('2024-01-31');
        $dueDate = new \DateTimeImmutable('2024-01-01');
        
        InvoiceDueDate::fromDueDate($dueDate, $issueDate);
    }

    #[Test]
    public function detects_overdue_invoices(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 10);
        
        $currentDate = new \DateTimeImmutable('2024-01-15');
        
        $this->assertTrue($dueDate->isOverdue($currentDate));
        $this->assertFalse($dueDate->isOverdue($issueDate));
    }

    #[Test]
    public function calculates_days_until_due(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        // Test future date
        $currentDate = new \DateTimeImmutable('2024-01-21');
        $this->assertEquals(10, $dueDate->getDaysUntilDue($currentDate));
        
        // Test overdue
        $overdueDate = new \DateTimeImmutable('2024-02-05');
        $this->assertEquals(-5, $dueDate->getDaysUntilDue($overdueDate));
    }

    #[Test]
    public function detects_due_soon_invoices(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        // Within 7 days
        $currentDate = new \DateTimeImmutable('2024-01-26');
        $this->assertTrue($dueDate->isDueSoon(7, $currentDate));
        
        // Not due soon
        $earlierDate = new \DateTimeImmutable('2024-01-15');
        $this->assertFalse($dueDate->isDueSoon(7, $earlierDate));
    }

    #[Test]
    public function formats_dates_correctly(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        $this->assertEquals('2024-01-31', $dueDate->formatDueDate());
        $this->assertEquals('2024-01-01', $dueDate->formatIssueDate());
        $this->assertEquals('31/01/2024', $dueDate->formatDueDate('d/m/Y'));
    }

    #[Test]
    public function provides_status_description(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 10);
        
        // Overdue
        $overdueDate = new \DateTimeImmutable('2024-01-15');
        $this->assertEquals('Overdue by 4 days', $dueDate->getStatusDescription($overdueDate));
        
        // Due soon
        $soonDate = new \DateTimeImmutable('2024-01-08');
        $this->assertEquals('Due in 3 days', $dueDate->getStatusDescription($soonDate));
        
        // Future
        $futureDate = new \DateTimeImmutable('2024-01-01');
        $this->assertEquals('Due in 10 days', $dueDate->getStatusDescription($futureDate));
    }

    #[Test]
    public function extends_due_date(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $originalDueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        $extendedDueDate = $originalDueDate->extend(15);
        
        $this->assertEquals(45, $extendedDueDate->getDueDays());
        $this->assertEquals('2024-02-15', $extendedDueDate->formatDueDate());
        $this->assertEquals($issueDate, $extendedDueDate->getIssueDate());
    }

    #[Test]
    public function throws_exception_for_non_positive_extension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Additional days must be positive, got: 0');
        
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        $dueDate->extend(0);
    }

    #[Test]
    public function provides_string_representation(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        
        $this->assertEquals('2024-01-31', (string) $dueDate);
    }

    #[Test]
    public function checks_equality(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate1 = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        $dueDate2 = InvoiceDueDate::fromIssueDateAndDays($issueDate, 30);
        $dueDate3 = InvoiceDueDate::fromIssueDateAndDays($issueDate, 25);
        
        $this->assertTrue($dueDate1->equals($dueDate2));
        $this->assertFalse($dueDate1->equals($dueDate3));
    }

    #[Test]
    public function handles_zero_due_days(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 0);
        
        $this->assertEquals(0, $dueDate->getDueDays());
        $this->assertEquals($issueDate, $dueDate->getDueDate());
        $this->assertEquals('2024-01-01', $dueDate->formatDueDate());
    }

    #[Test]
    public function handles_maximum_due_days(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $dueDate = InvoiceDueDate::fromIssueDateAndDays($issueDate, 365);
        
        $this->assertEquals(365, $dueDate->getDueDays());
        $this->assertEquals('2024-12-31', $dueDate->formatDueDate());
    }
}
