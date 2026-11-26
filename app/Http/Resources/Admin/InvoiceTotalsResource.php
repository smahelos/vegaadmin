<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Invoice\ValueObjects\VatLine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceTotalsResource extends JsonResource
{
    public function __construct(private readonly InvoiceTotals $totals)
    {
        parent::__construct($totals);
    }

    /** @return array<string, string|list<array<string, string>>> */
    public function toArray(Request $request): array
    {
        $total = $this->totals->total();
        $vatLines = array_map(
            static function (VatLine $vatLine): array {
                $vatLineTotal = $vatLine->total();

                return [
                    'rate' => $vatLine->rate,
                    'base' => $vatLine->base->amount,
                    'vat' => $vatLine->vat->amount,
                    'total' => $vatLineTotal->amount,
                ];
            },
            $this->totals->vatLines,
        );

        return [
            'vat_lines' => $vatLines,
            'subtotal' => $this->totals->subtotal->amount,
            'tax' => $this->totals->tax->amount,
            'total' => $total->amount,
            'currency' => $this->totals->subtotal->currency->value,
        ];
    }
}
