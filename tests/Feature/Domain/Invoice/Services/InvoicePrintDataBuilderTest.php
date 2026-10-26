<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Services\InvoicePrintDataBuilder;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class InvoicePrintDataBuilderTest extends TestCase
{
    #[Test]
    public function prints_the_tax_point_date_text_and_payment_method_of_the_stored_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'issue_date' => '2026-10-10',
            'tax_point_date' => '2026-09-30',
            'invoice_text' => 'Consulting in September',
            'payment_method_id' => PaymentMethod::where('slug', PaymentMethodCode::CASH->value)->firstOrFail()->id,
        ]);
        $dto = app(InvoiceDtoReadRepositoryInterface::class)->findByIdAny(InvoiceId::fromInt($invoice->id));

        $printData = app(InvoicePrintDataBuilder::class)->build($dto);

        $this->assertSame('2026-09-30', $printData->tax_point_date?->format('Y-m-d'));
        $this->assertSame('Consulting in September', $printData->notes);
        $this->assertSame(PaymentMethodCode::CASH, $printData->paymentMethod);
    }
}
