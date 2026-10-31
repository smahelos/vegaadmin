<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Invoice;

use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Tax;

final class AdminInvoiceItemFields
{
    /** @return array<string, mixed> */
    public static function field(?Invoice $invoice): array
    {
        $items = old('invoice-products');
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        $items ??= $invoice?->invoiceProducts->map(fn (InvoiceProduct $line): array => [
            'product_id' => $line->product_id,
            'name' => $line->name,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'price' => $line->price,
            'currency' => $line->currency,
            'tax_rate' => $line->tax_rate,
            'category' => $line->category,
            'description' => $line->description,
        ])->all() ?? [];

        $units = [];
        foreach (Unit::cases() as $unit) {
            $units[$unit->value] = __('invoices.units.' . $unit->value);
        }
        $taxRates = ['0'];
        foreach (Tax::query()->where('slug', 'dph')->pluck('rate') as $rate) {
            $taxRates[] = (string) $rate;
        }

        return [
            'name' => 'invoice-products',
            'type' => 'invoice_items',
            'label' => __('invoices.fields.invoice_items'),
            'wrapper' => ['class' => 'form-group col-12'],
            'items' => $items,
            'units' => $units,
            'currencies' => array_column(Currency::cases(), 'value'),
            'tax_rates' => array_values(array_unique($taxRates)),
        ];
    }
}
