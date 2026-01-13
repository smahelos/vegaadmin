<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\ValueObjects\InvoiceId;

/**
 * Domain port for synchronizing Invoice products from a source (e.g. invoice_text JSON) into persistence.
 * Hides Eloquent and storage details from the Domain layer.
 */
interface InvoiceProductsSyncRepositoryInterface
{
    /**
     * Synchronize products for given invoice ID from the underlying source (e.g. invoice_text JSON) to persistence.
     */
    public function syncFromInvoiceText(InvoiceId $invoiceId): void;

    /**
     * List invoice IDs that have non-empty source data for synchronization.
     *
     * @return array<int, InvoiceId>
     */
    public function listIdsWithInvoiceText(): array;
}
