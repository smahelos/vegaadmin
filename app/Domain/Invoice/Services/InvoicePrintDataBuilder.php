<?php

declare(strict_types=1);

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoicePrintDataBuilderInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceDTO;
use App\Domain\Invoice\DTO\PrintableInvoiceItemDTO;
use App\Domain\Invoice\ValueObjects\InvoiceDueDate;
use App\Domain\Party\DTO\InvoicingSettings;
use App\Domain\Party\DTO\PartyPrintDTO;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Enums\PaymentMethodCode;
use App\Domain\Payment\ValueObjects\PaymentMethodId;
use App\Domain\Payment\ValueObjects\VariableSymbol;

/**
 * Builds domain-clean printable data for invoices from InvoiceDTO.
 * No Eloquent, no presentation formatting, no IO.
 */
final class InvoicePrintDataBuilder implements InvoicePrintDataBuilderInterface
{
    public function __construct(
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
    ) {}

    public function build(InvoiceDTO $invoice, InvoicingSettings $business): PrintableInvoiceDTO
    {
        $issue = $invoice->issue;
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = new PrintableInvoiceItemDTO(
                name: $item->name,
                quantity: $item->quantity,
                unit: $item->unit,
                price: $item->price,
                taxRate: $item->taxRate,
                base: $item->base(),
            );
        }

        $supplier = $invoice->parties->supplier;
        $customer = $invoice->parties->customer;
        $currency = $invoice->totalAmount->currency;
        $dueDate = null;
        if ($issue !== null) {
            $dueDate = InvoiceDueDate::fromIssueDateAndDays($issue->date, $invoice->dueIn);
        }
        $number = $issue?->number->value;

        return new PrintableInvoiceDTO(
            id: $invoice->id,
            number: $number,
            issueDate: $issue?->date,
            taxPointDate: $issue?->taxPointDate,
            dueDate: $dueDate,
            dueIn: $invoice->dueIn,
            currency: $currency->value,
            template: $invoice->template,
            items: $items,
            totals: $invoice->totals,
            vatPayer: $issue?->vatPayer ?? $business->vatPayer,
            totalAmount: $invoice->totalAmount,
            supplier: PartyPrintDTO::fromDocumentParty($supplier),
            customer: PartyPrintDTO::fromDocumentParty($customer),
            account: $invoice->parties->account,
            supplierLogo: $invoice->parties->supplierLogo,
            variableSymbol: $number === null ? null : new VariableSymbol($number),
            constantSymbol: $invoice->constantSymbol,
            specificSymbol: $invoice->specificSymbol,
            paymentMethod: $this->paymentMethod($invoice->paymentMethodId),
            notes: $invoice->text,
            draft: $issue === null,
            registerEntry: $issue === null ? $business->registerEntry : $issue->registerEntry,
        );
    }

    private function paymentMethod(?PaymentMethodId $id): ?PaymentMethodCode
    {
        if ($id === null) {
            return null;
        }

        return $this->paymentMethods->findById($id)?->code;
    }
}
