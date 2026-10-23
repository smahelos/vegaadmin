<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Models\InvoiceProduct;

class EloquentInvoiceProductRepository implements InvoiceProductReadRepositoryInterface, InvoiceProductWriteRepositoryInterface
{
    public function create(array $data): InvoiceProduct
    {
        return InvoiceProduct::create($data);
    }

    public function allForInvoice(int $invoiceId): array
    {
        return InvoiceProduct::where('invoice_id', $invoiceId)
            ->with(relations: ['product'])
            ->get()
            ->toArray();
    }

    public function deleteByInvoiceId(int $invoiceId): void
    {
        InvoiceProduct::where('invoice_id', $invoiceId)->delete();
    }

    public function listForInvoiceWithProduct(int $invoiceId): array
    {
        return InvoiceProduct::where('invoice_id', $invoiceId)
            ->with(['product'])
            ->get()
            ->toArray();
    }
}
