<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceFormDTO;
use Illuminate\Http\Request;

/**
 * Contract for invoice form-related application services
 */
interface InvoiceFormApplicationServiceInterface
{
    /**
     * Prepare data for invoice creation form (legacy compatibility)
     */
    public function prepareCreateData(int $userId, Request $request): InvoiceFormDTO;

    /**
     * Prepare data for invoice editing form (legacy compatibility)
     */
    public function prepareEditData(
        int $userId,
        int $invoiceId,
        string $locale,
    ): InvoiceFormDTO;

    /**
     * Prepare data for invoice display (legacy compatibility)
     */
    public function prepareShowData(int $userId, int $invoiceId): InvoiceFormDTO;

    /**
     * Get next invoice number for user (legacy compatibility)
     */
    public function getNextInvoiceNumber(int $userId): string;
}
