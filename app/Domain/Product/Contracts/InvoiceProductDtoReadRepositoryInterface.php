<?php

namespace App\Domain\Product\Contracts;

use App\Domain\Invoice\ValueObjects\InvoiceId;


interface InvoiceProductDtoReadRepositoryInterface
{
    /**
     * Find all products for a specific invoice
     *
     * @param int $invoiceId
     * @return array
     */
    public function allForInvoice(InvoiceId $invoiceId): array;

    /** List products (with related product model) for an invoice */
    public function listForInvoiceWithProduct(InvoiceId $invoiceId): array;
}
