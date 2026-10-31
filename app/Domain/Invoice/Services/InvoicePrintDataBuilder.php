<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceItemDTO;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Party\DTO\PartyPrintDTO;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use DateMalformedStringException;
use DateTimeImmutable;

/**
 * Builds domain-clean printable data for invoices from InvoiceDTO.
 * No Eloquent, no presentation formatting, no IO.
 */
final class InvoicePrintDataBuilder implements InvoicePrintDataBuilderInterface
{
    public function __construct(
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
    ) {}

    public function build(InvoiceDTO $invoice): PrintableInvoiceDTO
    {
        $issue = $this->toImmutableDate($invoice->issue_date);
        $taxPoint = $this->toImmutableDate($invoice->tax_point_date);
        $due = $this->dueDate($issue, $invoice->due_in);

        $items = [];
        foreach ($invoice->items as $item) {
            $currency = strtoupper($item['currency'] ?? ($invoice->currency ?? 'CZK'));
            $price = ($item['price'] ?? null) instanceof Money ? $item['price'] : Money::fromString((string) ($item['price'] ?? '0'), $currency);
            $lineTotal = ($item['total_price'] ?? null) instanceof Money ? $item['total_price'] : Money::fromString((string) ($item['total_price'] ?? '0'), $currency);
            $items[] = new PrintableInvoiceItemDTO(
                name: (string) ($item['name'] ?? ''),
                quantity: isset($item['quantity']) ? (float) $item['quantity'] : 1.0,
                unit: ($item['unit'] ?? null) instanceof Unit ? $item['unit'] : Unit::fromInput($item['unit'] ?? null),
                price: $price,
                taxRate: isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0.0,
                lineTotal: $lineTotal,
            );
        }

        $supplier = $invoice->parties->supplier;
        $customer = $invoice->parties->customer;
        $account = $invoice->parties->account;

        return new PrintableInvoiceDTO(
            id: $invoice->id,
            number: $invoice->number,
            issueDate: $issue,
            taxPointDate: $taxPoint,
            dueDate: $due,
            currency: strtoupper($invoice->currency ?? 'CZK'),
            template: $invoice->template,
            items: $items,
            subtotal: $invoice->subtotal,
            totalTax: $invoice->total_tax,
            totalAmount: $invoice->total_amount,
            supplier: PartyPrintDTO::fromDocumentParty($supplier),
            customer: PartyPrintDTO::fromDocumentParty($customer),
            account: $account,
            supplierLogo: $invoice->parties->supplierLogo,
            variableSymbol: $invoice->number,
            constantSymbol: $invoice->constant_code,
            specificSymbol: $invoice->payment_reference,
            paymentMethod: $this->paymentMethod($invoice->payment_method_id),
            notes: $invoice->invoice_text,
        );
    }

    private function paymentMethod(?int $id): ?PaymentMethodCode
    {
        if ($id === null) {
            return null;
        }

        return $this->paymentMethods->findById(PaymentMethodId::fromInt($id))?->code;
    }

    private function toImmutableDate(?string $date): ?DateTimeImmutable
    {
        if (! $date) {
            return null;
        }
        try {
            return new DateTimeImmutable($date);
        } catch (DateMalformedStringException) {
            return null;
        }
    }

    private function dueDate(?DateTimeImmutable $issue, ?int $dueIn): ?InvoiceDueDate
    {
        if (! $issue || $dueIn === null) {
            return null;
        }

        return InvoiceDueDate::fromIssueDateAndDays($issue, $dueIn);
    }
}
