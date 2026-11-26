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

    public function build(InvoiceDTO $invoice): PrintableInvoiceDTO
    {
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = new PrintableInvoiceItemDTO(
                name: $item->name,
                quantity: (float) $item->quantity,
                unit: $item->unit,
                price: $item->price,
                taxRate: (float) $item->taxRate,
                lineTotal: $item->total,
            );
        }

        $supplier = $invoice->parties->supplier;
        $customer = $invoice->parties->customer;
        $currency = $invoice->totalAmount->currency;

        return new PrintableInvoiceDTO(
            id: $invoice->id,
            number: $invoice->number,
            issueDate: $invoice->issueDate,
            taxPointDate: $invoice->taxPointDate,
            dueDate: InvoiceDueDate::fromIssueDateAndDays($invoice->issueDate, $invoice->dueIn),
            currency: $currency->value,
            template: $invoice->template,
            items: $items,
            subtotal: $invoice->subtotal,
            totalTax: $invoice->totalTax,
            totalAmount: $invoice->totalAmount,
            supplier: PartyPrintDTO::fromDocumentParty($supplier),
            customer: PartyPrintDTO::fromDocumentParty($customer),
            account: $invoice->parties->account,
            supplierLogo: $invoice->parties->supplierLogo,
            variableSymbol: new VariableSymbol($invoice->number),
            constantSymbol: $invoice->constantSymbol,
            specificSymbol: $invoice->specificSymbol,
            paymentMethod: $this->paymentMethod($invoice->paymentMethodId),
            notes: $invoice->text,
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
