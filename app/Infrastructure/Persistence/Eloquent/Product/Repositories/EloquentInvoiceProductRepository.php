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
        $query = InvoiceProduct::where('invoice_id', $invoiceId);
        $query->with(relations: ['product']);
        $products = $query->get();

        return $products->toArray();
    }

    public function deleteByInvoiceId(int $invoiceId): void
    {
        $query = InvoiceProduct::where('invoice_id', $invoiceId);
        $query->delete();
    }
}
