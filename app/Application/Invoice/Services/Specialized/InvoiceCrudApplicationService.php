<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceCrudApplicationServiceInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceDtoWriteRepositoryInterface;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Product\Contracts\InvoiceProductDtoWriteRepositoryInterface;
use App\Domain\Product\Contracts\InvoiceProductDtoReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

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
        private InvoiceProductDtoWriteRepositoryInterface $invoiceProductWriteRepository,
        private InvoiceProductDtoReadRepositoryInterface $invoiceProductReadRepository
    ) {}

    /**
     * Create new invoice
     */
    public function create(array $createData, UserId $userId): InvoiceDTO
    {
        Log::debug('InvoiceCrudApplicationService.create called', [
            'userId' => $userId->toInt(),
            'hasProducts' => isset($createData['products']),
            'productsCount' => isset($createData['products']) ? count($createData['products']) : 0
        ]);
        
        // Process products and calculate totals
        $productsToCreate = $this->processProductsAndCalculateTotals($createData);
        
        // Create invoice through domain service - this will trigger domain events
        $createdInvoice = $this->invoiceService->createInvoice($userId, $createData, $productsToCreate ?? []);
        
        return $createdInvoice;
    }
    
    /**
     * Update existing invoice
     */
    public function update(array $updateData, InvoiceId $invoiceId, UserId $userId): InvoiceDTO
    {
        Log::debug('InvoiceCrudApplicationService.update called', [
            'invoiceId' => $invoiceId->getValue(),
            'userId' => $userId->toInt(),
            'hasProducts' => isset($updateData['products']),
            'productsCount' => isset($updateData['products']) ? count($updateData['products']) : 0
        ]);
        
        $invoice = $this->invoiceReadRepository->findForUser($userId->toInt(), $invoiceId->getValue());
        
        if (!$invoice) {
            throw new ModelNotFoundException("Invoice not found or access denied");
        }
        
        // Process products and calculate totals
        $productsToUpdate = $this->processProductsAndCalculateTotals($updateData, $invoiceId);
        
        $updatedInvoice = $this->invoiceWriteRepository->update($invoiceId, InvoiceWriteData::fromArray($updateData));
        
        return $updatedInvoice;
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

    /**
     * Process products, calculate totals, and prepare data for invoice creation/update
     * 
     * @param array $data Invoice data array (passed by reference, will be modified)
     * @param InvoiceId|null $invoiceId For updates, used to delete existing products
     * @return array|null Products array to create, or null if no products provided
     */
    private function processProductsAndCalculateTotals(array &$data, ?InvoiceId $invoiceId = null): ?array
    {
        if (!isset($data['products']) || !is_array($data['products'])) {
            return null;
        }

        $products = $data['products'];
        $operationType = $invoiceId ? 'update' : 'create';
        
        // For updates, delete existing products first
        if ($invoiceId) {
            $this->invoiceProductWriteRepository->deleteByInvoiceId($invoiceId);
            $this->invoiceProductWriteRepository->bulkCreate($invoiceId, $products);
            
            // Calculate totals from freshly created products
            $createdProducts = $this->invoiceProductReadRepository->allForInvoice($invoiceId);
            
            $subtotal = 0.0;
            $totalTax = 0.0;
            
            foreach ($createdProducts as $product) {
                $quantity = $product->quantity;
                $price = $product->price->toFloat();
                $taxAmount = $product->tax_amount->toFloat();
                
                $subtotal += ($quantity * $price);
                $totalTax += $taxAmount;
            }
        } else {
            // For creation, calculate totals from input data
            $subtotal = 0.0;
            $totalTax = 0.0;
            
            foreach ($products as $product) {
                $quantity = floatval($product['quantity'] ?? 0);
                $price = floatval($product['price'] ?? 0);
                $taxRate = floatval($product['tax_rate'] ?? 0);
                
                $itemSubtotal = $quantity * $price;
                $itemTax = $itemSubtotal * ($taxRate / 100);
                
                $subtotal += $itemSubtotal;
                $totalTax += $itemTax;
            }
        }
        
        $totalAmount = $subtotal + $totalTax;
        
        // Add calculated values to data for DTO creation
        $data['subtotal'] = $subtotal;
        $data['total_tax'] = $totalTax;
        $data['total_amount'] = $totalAmount;
        
        // Remove products from data as it's not part of invoice table
        unset($data['products']);
        
        // Return products for creation (only needed for create operation)
        return $invoiceId ? null : $products;
    }
}
