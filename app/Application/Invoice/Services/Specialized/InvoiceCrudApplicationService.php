<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Database\Contracts\TransactionBoundaryInterface;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class InvoiceCrudApplicationService implements InvoiceCrudApplicationServiceInterface
{
    public function __construct(
        private readonly InvoiceServiceInterface $invoiceService,
        private readonly InvoiceReadRepositoryInterface $invoiceReadRepository,
        private readonly InvoiceDtoWriteRepositoryInterface $invoiceWriteRepository,
        private readonly TransactionBoundaryInterface $transaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
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
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
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

        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not found or access denied');
        }

        $this->invoiceWriteRepository->delete($invoiceId);

        return true;
    }

    /**
     * Get invoice by ID for editing
     */
    public function getForEdit(InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());

        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not found or access denied');
        }

        return InvoiceDTO::fromArray($invoice->toArray());
    }

    /**
     * Soft delete invoice (if applicable)
     */
    public function softDelete(InvoiceId $invoiceId, UserId $userId): bool
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());

        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not found or access denied');
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
}
