<?php

namespace App\Infrastructure\Persistence\Eloquent\Invoice\Mappers;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Models\Invoice;

/**
 * Maps between Eloquent Invoice models and Invoice DTOs.
 * Keeps Domain layer independent from Eloquent specifics.
 */
class EloquentInvoiceMapper
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
        if (!is_array($items)) { $items = []; }

        return InvoiceDTO::fromArray([
            'id' => InvoiceId::fromInt((int) $model->getAttribute('id')),
            'user_id' => (int) $model->getAttribute('user_id'),
            'client_id' => $model->getAttribute('client_id') !== null ? (int) $model->getAttribute('client_id') : null,
            'supplier_id' => $model->getAttribute('supplier_id') !== null ? (int) $model->getAttribute('supplier_id') : null,
            'number' => $model->getAttribute('invoice_vs'),
            'payment_reference' => $model->getAttribute('invoice_ss'),
            'constant_code' => $model->getAttribute('invoice_ks'),
            'issue_date' => $model->getAttribute('issue_date'),
            'due_in' => $model->getAttribute('due_in') !== null ? (int) $model->getAttribute('due_in') : null,
            'subtotal' => $subtotal,
            'total_tax' => $totalTax,
            'note' => $model->getAttribute('invoice_text') ?? null,
            'total_amount' => $totalAmount,
            'currency' => $model->getAttribute('payment_currency') ?? null,
            'template' => $model->getAttribute('template') ?? null,
            'items' => $items,
            'created_at' => $model->getAttribute('created_at'),
            'updated_at' => $model->getAttribute('updated_at'),
            'status' => $status,
            'logo' => $model->getAttribute('invoice_logo'),
        ]);
    }

    /**
     * Convert write DTO into Eloquent attributes for persistence.
     * This keeps all column name mappings and totals normalization in one place.
     *
     * Note: It intentionally ignores DTO-only fields like items and status name.
     */
    public function toModelAttributes(InvoiceWriteData $data): array
    {
        $attrs = [];

        if ($data->user_id !== null) { $attrs['user_id'] = (int)$data->user_id; }
        if ($data->client_id !== null) { $attrs['client_id'] = (int)$data->client_id; }
        if ($data->supplier_id !== null) { $attrs['supplier_id'] = (int)$data->supplier_id; }

        if ($data->number !== null) { $attrs['invoice_vs'] = $data->number; }
        if ($data->payment_reference !== null) { $attrs['invoice_ss'] = $data->payment_reference; }
        if ($data->constant_code !== null) { $attrs['invoice_ks'] = $data->constant_code; }

        if ($data->issue_date !== null) { $attrs['issue_date'] = $data->issue_date; }
        if ($data->tax_point_date !== null) { $attrs['tax_point_date'] = $data->tax_point_date; }
        if ($data->due_in !== null) { $attrs['due_in'] = (int)$data->due_in; }

        if ($data->template !== null) { $attrs['template'] = $data->template; }
        if ($data->note !== null) { $attrs['invoice_text'] = $data->note; }
        if ($data->logo !== null) { $attrs['invoice_logo'] = $data->logo; }
        if ($data->status !== null) { $attrs['payment_status_id'] = (int)$data->status; }

        if ($data->payment_method_id !== null) { $attrs['payment_method_id'] = (int)$data->payment_method_id; }

        // Currency may come from total_amount Money or explicit currency
        if ($data->currency !== null) {
            $attrs['payment_currency'] = strtoupper($data->currency);
        }
        if ($data->total_amount !== null) {
            $attrs['payment_amount'] = number_format((float)$data->total_amount->getAmount(), 2, '.', '');
            $attrs['payment_currency'] = $data->total_amount->getCurrency();
        }
        // We do not persist subtotal/total_tax directly; they are derived from products

        // Timestamps can be optionally overridden
        if ($data->created_at !== null) { $attrs['created_at'] = $data->created_at; }
        if ($data->updated_at !== null) { $attrs['updated_at'] = $data->updated_at; }

        return $attrs;
    }
}
