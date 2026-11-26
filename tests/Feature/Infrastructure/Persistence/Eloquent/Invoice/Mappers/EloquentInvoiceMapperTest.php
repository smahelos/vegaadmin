<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Invoice\Mappers\EloquentInvoiceMapper;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ValueError;

final class EloquentInvoiceMapperTest extends TestCase
{
    #[Test]
    public function the_subtotal_and_vat_are_summed_from_the_stored_lines(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            [
                'payment_amount' => '410.00',
                'payment_currency' => Currency::USD->value,
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        // The totals are computed by rate from the price, quantity and rate of each line
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'quantity' => '2',
                'price' => '100.00',
                'currency' => Currency::USD->value,
                'tax_rate' => '21',
            ],
        );
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'quantity' => '3',
                'price' => '50.00',
                'currency' => Currency::USD->value,
                'tax_rate' => '12',
            ],
        );
        $mapper = new EloquentInvoiceMapper;

        $dto = $mapper->toDto($invoice);

        $this->assertTrue(
            $dto->totals->subtotal->equals(
                Money::fromString('350', Currency::USD),
            ),
        );
        $this->assertTrue(
            $dto->totals->tax->equals(
                Money::fromString('60', Currency::USD),
            ),
        );
        $this->assertTrue(
            $dto->totalAmount->equals(
                Money::fromString('410', Currency::USD),
            ),
        );
        $this->assertCount(2, $dto->items);
        $this->assertCount(2, $dto->totals->vatLines);
    }

    #[Test]
    public function an_empty_currency_is_refused_instead_of_assuming_czk(): void
    {
        $invoice = new Invoice;
        $invoice->payment_currency = '';
        $mapper = new EloquentInvoiceMapper;

        $this->expectException(ValueError::class);
        $mapper->toDto($invoice);
    }
}
