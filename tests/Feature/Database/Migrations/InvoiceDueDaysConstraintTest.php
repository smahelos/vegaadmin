<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Models\Invoice;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Every stored invoice has due days InvoiceDueDate accepts, so its due date can always be computed.
 */
final class InvoiceDueDaysConstraintTest extends TestCase
{
    #[Test]
    public function due_days_within_the_range_are_stored(): void
    {
        $first = Invoice::factory()->create(['due_in' => 0]);
        $last = Invoice::factory()->create(['due_in' => InvoiceDueDate::MAX_DAYS]);

        $this->assertDatabaseHas('invoices', ['id' => $first->id, 'due_in' => 0]);
        $this->assertDatabaseHas('invoices', ['id' => $last->id, 'due_in' => InvoiceDueDate::MAX_DAYS]);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function dueDaysOutsideTheRange(): iterable
    {
        yield 'negative' => [-1];
        yield 'above the maximum' => [InvoiceDueDate::MAX_DAYS + 1];
    }

    #[Test]
    #[DataProvider('dueDaysOutsideTheRange')]
    public function due_days_outside_the_range_are_refused(int $dueDays): void
    {
        $invoice = Invoice::factory()->create();

        $this->expectException(QueryException::class);

        $invoice->forceFill(['due_in' => $dueDays])->save();
    }
}
