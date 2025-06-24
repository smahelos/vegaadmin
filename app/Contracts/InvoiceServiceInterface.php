<?php

namespace App\Contracts;

use App\Models\Invoice;

interface InvoiceServiceInterface
{
    /**
     * Generate next available invoice number
     * 
     * @return string
     */
    public function getNextInvoiceNumber(): string;

    /**
     * Get item units for invoice items
     *
     * @return array
     */
    public function getItemUnits(): array;

    /**
     * Save products to an invoice
     *
     * @param Invoice $invoice The invoice
     * @param array $products Array of products data
     * @return void
     */
    public function saveInvoiceProducts(Invoice $invoice, array $products): void;

    /**
     * Store temporary invoice data for PDF generation
     *
     * @param array $data Invoice data
     * @return string Temporary token
     */
    public function storeTemporaryInvoice(array $data): string;

    /**
     * Get temporary invoice data by token
     *
     * @param string $token Temporary token
     * @return array|null Invoice data or null if not found
     */
    public function getTemporaryInvoiceByToken(string $token): ?array;

    /**
     * Delete temporary invoice data
     *
     * @param string $token Temporary token
     * @return bool Success status
     */
    public function deleteTemporaryInvoice(string $token): bool;

    /**
     * Mark invoice as paid
     *
     * @param int $id Invoice ID
     * @return bool Success status
     */
    public function markInvoiceAsPaid(int $id): bool;

    /**
     * Ensure object has required properties with default values
     *
     * @param \stdClass $object Object to modify
     * @param array $properties Properties with default values
     * @return void
     */
    public function ensureObjectProperties(\stdClass $object, array $properties): void;
}
