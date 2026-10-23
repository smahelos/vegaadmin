<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice CRUD operations
 */
class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private InvoiceServiceInterface $invoiceService,
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private StatusDtoRepositoryInterface $statusRepository,
        private TransactionBoundaryInterface $transaction,
    ) {}

    /**
     * @param array<string, mixed> $data
     * @param list<InvoiceProductWriteData> $products
     */
    public function create(UserId $userId, array $data, array $products): InvoiceDTO
    {
        return $this->transaction->transaction(
            fn (): InvoiceDTO => $this->invoiceService->createInvoice($userId, $data, $products)
        );
    }

    /**
     * The invoice and its lines are replaced together; a failure leaves the stored invoice untouched.
     *
     * @param array<string, mixed> $data
     * @param list<InvoiceProductWriteData> $products
     */
    public function update(UserId $userId, InvoiceId $invoiceId, array $data, array $products): InvoiceDTO
    {
        return $this->transaction->transaction(
            fn (): InvoiceDTO => $this->invoiceService->updateInvoice($userId, $invoiceId, $data, $products)
        );
    }

    /**
     * Delete invoice
     */
    public function delete(InvoiceId $invoiceId, UserId $userId): bool
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }

        $this->invoiceWriteRepository->delete($invoiceId);

        return true;
    }

    /**
     * Duplicate existing invoice
     */
    public function duplicate(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $originalInvoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$originalInvoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }
        
        // Create duplicate data
        $duplicateData = $originalInvoice->toArray();
        
        // Remove unique fields and modify for duplication
        unset($duplicateData['id']);
        unset($duplicateData['created_at']);
        unset($duplicateData['updated_at']);
        
        // Modify invoice number to indicate duplicate
        $duplicateData['invoice_vs'] = $this->generateDuplicateInvoiceNumber($duplicateData['invoice_vs'] ?? '');
        $duplicateData['issue_date'] = now()->format('Y-m-d');
        $duplicateData['due_date'] = now()->addDays(14)->format('Y-m-d');

        $duplicatedInvoice = $this->invoiceWriteRepository->create(InvoiceWriteData::fromArray($duplicateData));

        return $duplicatedInvoice;
    }

    /**
     * Get invoice by ID for editing
     */
    public function getForEdit(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }
        
        return InvoiceDTO::fromArray($invoice->toArray());
    }

    /**
     * Soft delete invoice (if applicable)
     */
    public function softDelete(InvoiceId $invoiceId, UserId $userId): bool
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }
        
        // Use soft delete if available, otherwise regular delete
        if (method_exists($invoice, 'delete')) {
            return (bool) $invoice->delete();
        }
        
        return $this->delete($invoiceId, $userId);
    }

    /**
     * Restore soft-deleted invoice (if applicable)
     */
    public function restore(InvoiceId $invoiceId, UserId $userId): bool
    {
        // Implementation depends on soft delete implementation
        // For now, just return false
        return false;
    }

    /**
     * Generate duplicate invoice number
     */
    private function generateDuplicateInvoiceNumber(string $originalNumber): string
    {
        // Add duplicate suffix or increment
        if (preg_match('/^(.+)-DUP(\d*)$/', $originalNumber, $matches)) {
            $base = $matches[1];
            $dupNumber = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] + 1 : 2;
            return $base . '-DUP' . $dupNumber;
        }
        
        return $originalNumber . '-DUP';
    }
}
