<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentDocumentPartiesMapper;
use App\Models\Invoice;

final class EloquentInvoiceMapper
{
    /**
     * Convert Eloquent Invoice model to InvoiceDTO.
     */
    public function toDto(Invoice $model): InvoiceDTO
    {
        // Prefer Money VO accessors to avoid float issues
        $subtotal = $model->getAttribute('subtotal_money') ?? $model->subtotal_money ?? null;
        $totalTax = $model->getAttribute('total_tax_money') ?? $model->total_tax_money ?? null;
        $totalAmount = $model->getAttribute('payment_amount_money') ?? $model->payment_amount_money ?? null;

        $status = $model->getAttribute('payment_status_name') ?? $model->payment_status_name ?? null;

        // Include items using computed accessor; repository will eager-load relation to avoid N+1
        // This returns an array shape like: [ ['name'=>..., 'quantity'=>..., 'price'=>..., 'currency'=>..., 'tax_rate'=>..., 'tax_amount'=>..., 'total_price'=>...], ... ]
        $items = $model->getAttribute('invoice_products_data') ?? $model->invoice_products_data ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        return InvoiceDTO::fromArray(
            [
                'id' => InvoiceId::fromInt(
                    (int) $model->getAttribute('id'),
                ),
                'user_id' => (int) $model->getAttribute('user_id'),
                'parties' => EloquentDocumentPartiesMapper::toDocumentParties($model),
                'number' => $model->getAttribute('invoice_vs'),
                'payment_reference' => $model->getAttribute('invoice_ss'),
                'constant_code' => $model->getAttribute('invoice_ks'),
                'issue_date' => $model->getAttribute('issue_date'),
                'tax_point_date' => $model->tax_point_date?->format('Y-m-d'),
                'due_in' => $model->getAttribute('due_in') !== null ? (int) $model->getAttribute('due_in') : null,
                'subtotal' => $subtotal,
                'total_tax' => $totalTax,
                'payment_method_id' => $model->payment_method_id,
                'invoice_text' => $model->invoice_text,
                'total_amount' => $totalAmount,
                'currency' => $model->getAttribute('payment_currency') ?? null,
                'template' => $model->getAttribute('template') ?? null,
                'items' => $items,
                'created_at' => $model->getAttribute('created_at'),
                'status' => $status,
            ],
        );
    }

    /**
     * The status is stored as a reference to its statuses row, which the repository resolves.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(
        InvoiceWriteData $data,
        DocumentParties $parties,
        Money $total,
    ): array {
        return [
            ...EloquentDocumentPartiesMapper::toAttributes($parties),
            'invoice_vs' => $data->number->getNumber(),
            'invoice_ks' => $data->constantSymbol,
            'invoice_ss' => $data->specificSymbol,
            'issue_date' => $data->issueDate->format('Y-m-d'),
            'tax_point_date' => $data->taxPointDate->format('Y-m-d'),
            'due_in' => $data->dueIn,
            'payment_method_id' => $data->paymentMethodId->getValue(),
            'payment_currency' => $data->currency->value,
            'payment_amount' => $total->getAmount(),
            'invoice_text' => $data->text,
            'template' => $data->template->value,
        ];
    }
}
