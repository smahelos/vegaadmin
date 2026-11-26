<?php

declare(strict_types=1);

namespace App\Application\Invoice\Presenters;

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
     * @return list<array{product_id: ?int, name: string, quantity: string, unit: string, price: string, currency: string, tax_rate: string}>
     */
    public function items(InvoiceDTO $invoice): array
    {
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = [
                'product_id' => $item->productId?->value,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit->value,
                'price' => $item->price->amount,
                'currency' => $item->price->currency->value,
                'tax_rate' => $item->taxRate,
            ];
        }

        return $items;
    }
}
