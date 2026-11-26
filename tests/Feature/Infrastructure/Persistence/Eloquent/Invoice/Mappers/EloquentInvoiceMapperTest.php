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
                'payment_amount' => '389.00',
                'payment_currency' => Currency::USD->value,
            ],
        );
        $invoiceProductFactory = InvoiceProduct::factory();
        // The base is recomputed from price and quantity, the VAT is taken as stored with each line
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'quantity' => '2',
                'price' => '100.00',
                'currency' => Currency::USD->value,
                'tax_rate' => '21',
                'tax_amount' => '21.00',
                'total_price' => '242.00',
            ],
        );
        $invoiceProductFactory->create(
            [
                'invoice_id' => $invoice->id,
                'quantity' => '3',
                'price' => '50.00',
                'currency' => Currency::USD->value,
                'tax_rate' => '12',
                'tax_amount' => '18.00',
                'total_price' => '168.00',
            ],
        );
        $mapper = new EloquentInvoiceMapper;

        $dto = $mapper->toDto($invoice);

        $this->assertTrue(
            $dto->subtotal->equals(
                Money::fromString('350', Currency::USD),
            ),
        );
        $this->assertTrue(
            $dto->totalTax->equals(
                Money::fromString('39', Currency::USD),
            ),
        );
        $this->assertTrue(
            $dto->totalAmount->equals(
                Money::fromString('389', Currency::USD),
            ),
        );
        $this->assertCount(2, $dto->items);
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
