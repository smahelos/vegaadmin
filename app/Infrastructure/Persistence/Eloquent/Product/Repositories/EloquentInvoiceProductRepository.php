<?php

namespace App\Infrastructure\Persistence\Eloquent\Product\Repositories;

use App\Application\Product\Contracts\InvoiceProductWriteRepositoryInterface;
use App\Models\InvoiceProduct;

class EloquentInvoiceProductRepository implements InvoiceProductWriteRepositoryInterface
{
    public function create(array $data): InvoiceProduct
    {
        return InvoiceProduct::create($data);
    }

    public function deleteByInvoiceId(int $invoiceId): void
    {
        $query = InvoiceProduct::where('invoice_id', $invoiceId);
        $query->delete();
    }
}
