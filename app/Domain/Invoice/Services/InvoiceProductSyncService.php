<?php

namespace App\Domain\Invoice\Services;

use App\Domain\Invoice\Contracts\InvoiceProductSyncServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceProductsSyncRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Log\Contracts\LogInterface;

class InvoiceProductSyncService implements InvoiceProductSyncServiceInterface
{
    public function __construct(
        private readonly TransactionBoundaryInterface $tx,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceProductsSyncRepositoryInterface $syncRepo,
        private readonly LogInterface $logger
    ) {}

    /**
     * Synchronize products from invoice_text JSON to persistence by invoice ID.
     */
    public function syncProductsFromJson(InvoiceId $invoiceId): void
    {
        try {
            $this->tx->transaction(function () use ($invoiceId) {
                $this->syncRepo->syncFromInvoiceText($invoiceId);
            });
        } catch (\Exception $e) {
            $this->logger->log('error', 'Failed to sync invoice products: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk update of all invoices
     * Use with caution - might be resource intensive for large databases
     */
    public function syncAllInvoices(): void
    {
        foreach ($this->syncRepo->listIdsWithInvoiceText() as $invoiceId) {
            if ($invoiceId instanceof InvoiceId) {
                $this->syncProductsFromJson($invoiceId);
            }
        }
    }
}
