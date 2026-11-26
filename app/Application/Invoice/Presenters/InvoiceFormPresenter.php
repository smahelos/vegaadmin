<?php

declare(strict_types=1);

namespace App\Application\Invoice\Presenters;

use App\Application\Invoice\DTO\EditableInvoiceItemDTO;
use App\Domain\Invoice\DTO\InvoiceDTO;

/**
 * The stored invoice as the values of the edit form, keyed by the request keys of InvoiceRequest.
 */
final class InvoiceFormPresenter
{
    private const string DATE_INPUT = 'Y-m-d';

    /** @return array<string, string> */
    public function values(InvoiceDTO $invoice): array
    {
        return [
            'invoice_vs' => $invoice->number,
            'invoice_ks' => $invoice->constantSymbol ?? '',
            'invoice_ss' => $invoice->specificSymbol ?? '',
            'status' => $invoice->status->value,
            'issue_date' => $invoice->issueDate->format(self::DATE_INPUT),
            'tax_point_date' => $invoice->taxPointDate->format(self::DATE_INPUT),
            'payment_method_id' => $invoice->paymentMethodId === null ? '' : (string) $invoice->paymentMethodId->value,
            'due_in' => (string) $invoice->dueIn,
            'payment_amount' => $invoice->totalAmount->amount,
            'payment_currency' => $invoice->totalAmount->currency->value,
            'invoice_text' => $invoice->text ?? '',
        ];
    }

    /**
     * The lines for the item editor; a line shows the name stored on the invoice.
     *
     * @return list<EditableInvoiceItemDTO>
     */
    public function items(InvoiceDTO $invoice): array
    {
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = new EditableInvoiceItemDTO(
                productId: $item->productId,
                name: $item->name,
                quantity: $item->quantity,
                unit: $item->unit,
                price: $item->price,
                taxRate: $item->taxRate,
            );
        }

        return $items;
    }
}
