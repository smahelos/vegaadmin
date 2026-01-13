<?php

namespace App\Application\Invoice\Contracts;

use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

/**
 * Contract for invoice form-related application services
 */
interface InvoiceFormApplicationServiceInterface
{
    /**
     * Get comprehensive form data for invoice creation/editing
     */
    public function getFormData(UserId $userId, ?InvoiceId $invoiceId = null): array;

    /**
     * Prepare form data with defaults and options
     */
    public function prepareFormData(UserId $userId, ?InvoiceId $invoiceId = null): array;

    /**
     * Load default values for new invoice
     */
    public function loadDefaults(UserId $userId): array;

    /**
     * Get select options for form dropdowns
     */
    public function getSelectOptions(UserId $userId): array;

    /**
     * Get available products for user
     */
    public function getProducts(UserId $userId): Collection;

    /**
     * Get available clients for user
     */
    public function getClients(UserId $userId): Collection;

    /**
     * Get available suppliers for user
     */
    public function getSuppliers(UserId $userId): Collection;

    /**
     * Get available banks for user
     */
    public function getBanks(UserId $userId): Collection;

    /**
     * Get available payment methods for user
     */
    public function getPaymentMethods(UserId $userId): Collection;

    /**
     * Get available taxes for user
     */
    public function getTaxes(UserId $userId): Collection;

    /**
     * Prepare data for invoice creation form (legacy compatibility)
     */
    public function prepareCreateData(int $userId, Request $request): InvoiceFormDTO;

    /**
     * Prepare data for invoice editing form (legacy compatibility)
     */
    public function prepareEditData(int $userId, int $invoiceId, string $locale): InvoiceFormDTO;

    /**
     * Prepare data for invoice display (legacy compatibility)
     */
    public function prepareShowData(int $userId, int $invoiceId): InvoiceFormDTO;

    /**
     * Get item units for invoice items (legacy compatibility)
     */
    public function getItemUnits(): array;

    /**
     * Get next invoice number for user (legacy compatibility)
     */
    public function getNextInvoiceNumber(int $userId): string;
}
