<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\ValueObjects;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceNumberTest extends TestCase
{
    #[Test]
    public function creates_valid_invoice_number(): void
    {
        $invoiceNumber = new InvoiceNumber('20250001');

        $this->assertEquals('20250001', $invoiceNumber->value);
        $this->assertEquals(2025, $invoiceNumber->year());
        $this->assertEquals(1, $invoiceNumber->sequence());
    }

    #[Test]
    public function creates_from_year_and_sequence(): void
    {
        $invoiceNumber = InvoiceNumber::fromYearAndSequence(2025, 123);

        $this->assertEquals('20250123', $invoiceNumber->value);
        $this->assertEquals(2025, $invoiceNumber->year());
        $this->assertEquals(123, $invoiceNumber->sequence());
    }

    #[Test]
    public function generates_next_number_of_the_year(): void
    {
        $invoiceNumber = InvoiceNumber::generateNext(2025, '20250005');

        $this->assertSame('20250006', $invoiceNumber->value);
    }

    #[Test]
    public function refuses_a_last_number_of_another_year(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InvoiceNumber::generateNext(2026, '20250005');
    }

    #[Test]
    public function generates_first_number_of_a_year_without_numbers(): void
    {
        $invoiceNumber = InvoiceNumber::generateNext(2024, null);

        $this->assertSame('20240001', $invoiceNumber->value);
    }

    #[Test]
    public function compares_invoice_numbers(): void
    {
        $number1 = new InvoiceNumber('20250001');
        $number2 = new InvoiceNumber('20250002');
        $number3 = new InvoiceNumber('20240999');

        $this->assertTrue(
            $number2->isGreaterThan($number1),
        );
        $this->assertTrue(
            $number1->isGreaterThan($number3),
        );
        $this->assertFalse(
            $number1->isGreaterThan($number2),
        );
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
        $this->expectExceptionMessage('Sequence must be between 1 and 999999');

        InvoiceNumber::fromYearAndSequence(2025, 0);
    }

    #[Test]
    public function continues_with_five_digit_sequence_after_9999(): void
    {
        $invoiceNumber = InvoiceNumber::generateNext(2025, '20259999');

        $this->assertSame('202510000', $invoiceNumber->value);
        $this->assertSame(10000, $invoiceNumber->sequence());
    }

    #[Test]
    public function parses_longer_sequence_and_compares_numerically(): void
    {
        $longer = new InvoiceNumber('202510000');

        $this->assertSame(2025, $longer->year());
        $this->assertTrue(
            $longer->isGreaterThan(
                new InvoiceNumber('20259999'),
            ),
        );
    }

    #[Test]
    public function rejects_non_canonical_padding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid invoice number format');

        new InvoiceNumber('2025010000');
    }

    #[Test]
    public function throws_when_yearly_sequence_is_exhausted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invoice number sequence for year 2025 is exhausted');

        InvoiceNumber::generateNext(2025, '2025999999');
    }
}
