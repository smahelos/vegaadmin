<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Invoice\DTO\InvoiceProductDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;

interface InvoiceProductDtoWriteRepositoryInterface
{
    public function create(InvoiceId $invoiceId, InvoiceProductWriteData $data): InvoiceProductDTO;

    /**
     * @param  array<int, InvoiceProductWriteData>  $products
     */
    public function bulkCreate(InvoiceId $invoiceId, array $products): void;

    public function deleteByInvoiceId(InvoiceId $invoiceId): void;
}
