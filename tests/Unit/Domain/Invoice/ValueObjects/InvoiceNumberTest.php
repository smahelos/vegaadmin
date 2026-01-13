<?php

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceNumberTest extends TestCase
{
    #[Test]
    public function creates_valid_invoice_number(): void
    {
        $invoiceNumber = new InvoiceNumber('20250001');
        
        $this->assertEquals('20250001', $invoiceNumber->getNumber());
        $this->assertEquals(2025, $invoiceNumber->getYear());
        $this->assertEquals(1, $invoiceNumber->getSequence());
    }

    #[Test] 
    public function creates_from_year_and_sequence(): void
    {
        $invoiceNumber = InvoiceNumber::fromYearAndSequence(2025, 123);
        
        $this->assertEquals('20250123', $invoiceNumber->getNumber());
        $this->assertEquals(2025, $invoiceNumber->getYear());
        $this->assertEquals(123, $invoiceNumber->getSequence());
    }

    #[Test]
    public function generates_next_number_for_new_sequence(): void
    {
        // Mock current year as 2025
        $invoiceNumber = InvoiceNumber::generateNext('20250005');
        
        $this->assertEquals(2025, $invoiceNumber->getYear());
        $this->assertEquals(6, $invoiceNumber->getSequence());
    }

    #[Test]
    public function generates_first_number_when_no_last_number(): void
    {
        $invoiceNumber = InvoiceNumber::generateNext();
        
        $this->assertEquals((int) date('Y'), $invoiceNumber->getYear());
        $this->assertEquals(1, $invoiceNumber->getSequence());
    }

    #[Test]
    public function formats_with_separator(): void
    {
        $invoiceNumber = new InvoiceNumber('20250123');
        
        $this->assertEquals('2025-0123', $invoiceNumber->formatWithSeparator('-'));
        $this->assertEquals('2025/0123', $invoiceNumber->formatWithSeparator('/'));
    }

    #[Test]
    public function checks_if_from_current_year(): void
    {
        $currentYear = (int) date('Y');
        $invoiceNumber = InvoiceNumber::fromYearAndSequence($currentYear, 1);
        
        $this->assertTrue($invoiceNumber->isFromCurrentYear());
        
        $oldInvoiceNumber = InvoiceNumber::fromYearAndSequence($currentYear - 1, 1);
        $this->assertFalse($oldInvoiceNumber->isFromCurrentYear());
    }

    #[Test]
    public function compares_invoice_numbers(): void
    {
        $number1 = new InvoiceNumber('20250001');
        $number2 = new InvoiceNumber('20250002');
        $number3 = new InvoiceNumber('20240999');
        
        $this->assertTrue($number2->isGreaterThan($number1));
        $this->assertTrue($number1->isGreaterThan($number3));
        $this->assertFalse($number1->isGreaterThan($number2));
    }

    #[Test]
    public function checks_equality(): void
    {
        $number1 = new InvoiceNumber('20250001');
        $number2 = new InvoiceNumber('20250001');
        $number3 = new InvoiceNumber('20250002');
        
        $this->assertTrue($number1->equals($number2));
        $this->assertFalse($number1->equals($number3));
    }

    #[Test]
    public function converts_to_string(): void
    {
        $invoiceNumber = new InvoiceNumber('20250123');
        
        $this->assertEquals('20250123', (string) $invoiceNumber);
    }

    #[Test]
    public function throws_exception_for_invalid_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid invoice number format');
        
        new InvoiceNumber('invalid');
    }

    #[Test]
    public function throws_exception_for_invalid_year(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid year in invoice number');
        
        new InvoiceNumber('19990001');
    }

    #[Test]
    public function throws_exception_for_invalid_sequence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sequence in invoice number');
        
        new InvoiceNumber('20250000');
    }

    #[Test]
    public function throws_exception_for_out_of_range_year_in_factory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be between 2000 and 9999');
        
        InvoiceNumber::fromYearAndSequence(1999, 1);
    }

    #[Test]
    public function throws_exception_for_out_of_range_sequence_in_factory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sequence must be between 1 and 9999');
        
        InvoiceNumber::fromYearAndSequence(2025, 0);
    }
}
