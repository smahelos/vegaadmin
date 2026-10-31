<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceListDTO;
use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Invoice;

final class EloquentInvoiceListMapper
{
    public static function toDTO(Invoice $invoice): InvoiceListDTO
    {
        return new InvoiceListDTO(
            id: InvoiceId::fromInt($invoice->id),
            contactId: $invoice->contact_id === null ? null : ContactId::fromInt($invoice->contact_id),
            number: $invoice->invoice_vs,
            customerName: $invoice->customer_name,
            amount: Money::fromString((string) $invoice->payment_amount, $invoice->payment_currency),
            dueDate: $invoice->issue_date === null || $invoice->due_in === null ? null : InvoiceDueDate::fromIssueDateAndDays($invoice->issue_date->toDateTimeImmutable(), $invoice->due_in),
            status: InvoiceStatus::tryFrom($invoice->paymentStatus?->slug ?? ''),
            paymentMethod: PaymentMethodCode::tryFrom($invoice->paymentMethod?->slug ?? ''),
            statusColor: $invoice->paymentStatus?->color ?: 'gray',
        );
    }

    public static function businessFilter(Business $business): InvoicePartyFilterDTO
    {
        return new InvoicePartyFilterDTO(
            BusinessId::fromInt($business->id),
            $business->name,
            $business->archived_at !== null,
        );
    }

    public static function contactFilter(Contact $contact): InvoicePartyFilterDTO
    {
        return new InvoicePartyFilterDTO(
            ContactId::fromInt($contact->id),
            $contact->name,
            $contact->archived_at !== null,
        );
    }
}
