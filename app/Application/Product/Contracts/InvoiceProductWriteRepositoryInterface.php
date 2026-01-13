<?php

namespace App\Application\Product\Contracts;

use App\Models\InvoiceProduct;


interface InvoiceProductWriteRepositoryInterface
{
    public function create(array $data): InvoiceProduct;
    
    public function allForInvoice(int $invoiceId): array;

    public function bulkCreate(int $invoiceId, array $products): void;

    public function deleteByInvoiceId(int $invoiceId): void;

    public function listForInvoiceWithProduct(int $invoiceId): array;
}
