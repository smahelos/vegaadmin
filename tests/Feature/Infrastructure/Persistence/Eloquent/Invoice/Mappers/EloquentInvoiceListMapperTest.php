<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceListMapper;
use App\Models\Invoice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class EloquentInvoiceListMapperTest extends TestCase
{
    /** @return array<string, array{string|null, int|null}> */
    public static function incompleteDueDates(): array
    {
        return [
            'no issue date' => [null, 14],
            'no due days' => ['2026-01-10', null],
            'neither' => [null, null],
        ];
    }

    #[Test]
    public function the_due_date_comes_from_the_issue_date_and_days(): void
    {
        $dto = EloquentInvoiceListMapper::toDTO(
            $this->invoice('2026-01-10', 14),
        );

        $this->assertInstanceOf(InvoiceDueDate::class, $dto->dueDate);
        $this->assertSame(
            '2026-01-10',
            $dto->dueDate->issueDate->format('Y-m-d'),
        );
        $this->assertSame(14, $dto->dueDate->days);
    }

    #[Test]
    #[DataProvider('incompleteDueDates')]
    public function an_incomplete_due_date_stays_empty(?string $issueDate, ?int $days): void
    {
        $dto = EloquentInvoiceListMapper::toDTO(
            $this->invoice($issueDate, $days),
        );

        $this->assertNull($dto->dueDate);
    }

    private function invoice(?string $issueDate, ?int $days): Invoice
    {
        $invoice = new Invoice;
        $invoice->forceFill(
            [
                'id' => 5,
                'invoice_vs' => '2026000001',
                'customer_name' => 'Customer',
                'payment_amount' => '1.00',
                'payment_currency' => 'CZK',
                'issue_date' => $issueDate,
                'due_in' => $days,
            ],
        );

        return $invoice;
    }
}
