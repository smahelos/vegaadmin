<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceProductDTO;


interface InvoiceProductDtoWriteRepositoryInterface
{
    public function create(array $data): InvoiceProductDTO;
    
    public function allForInvoice(InvoiceId $invoiceId): array;

    public function bulkCreate(InvoiceId $invoiceId, array $products): void;

    public function deleteByInvoiceId(InvoiceId $invoiceId): void;

    public function listForInvoiceWithProduct(InvoiceId $invoiceId): array;
}
