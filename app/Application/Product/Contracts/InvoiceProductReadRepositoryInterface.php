<?php

namespace App\Application\Product\Contracts;

interface InvoiceProductReadRepositoryInterface
{
    /**
     * Find all products for a specific invoice
     */
    public function allForInvoice(int $invoiceId): array;

    /** List products (with related product model) for an invoice */
    public function listForInvoiceWithProduct(int $invoiceId): array;
}
