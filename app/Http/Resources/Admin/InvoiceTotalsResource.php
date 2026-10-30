<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceTotalsResource extends JsonResource
{
    public function __construct(private readonly InvoiceTotals $totals)
    {
        parent::__construct($totals);
    }

    /** @return array<string, string> */
    public function toArray(Request $request): array
    {
        return [
            'subtotal' => $this->totals->subtotal->getAmount(),
            'tax' => $this->totals->tax->getAmount(),
            'total' => $this->totals->total()->getAmount(),
            'currency' => $this->totals->subtotal->getCurrency(),
        ];
    }
}
