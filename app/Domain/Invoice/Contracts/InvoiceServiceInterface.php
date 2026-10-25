<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

interface InvoiceServiceInterface
{
    // /**
    //  * Save products to an invoice
    //  *
    //  * @param int $invoiceId The invoice ID
    //  * @param array $products Array of products data
    //  * @return void
    //  */
    // public function saveInvoiceProducts(int $invoiceId, array $products): void;

    /**
     * Set invoice pdf template
     *
     * @param  InvoiceId  $id  Invoice ID
     * @param  string  $template  Template name
     * @return bool Success status
     */
    public function setInvoiceTemplate(InvoiceId $id, string $template): bool;

    /**
     * Change invoice status
     *
     * @param  UserId  $userId  Owner user id
     * @param  InvoiceId  $invoiceId  Invoice id
     * @param  int  $statusId  New status id
     */
    public function changeInvoiceStatus(
        UserId $userId,
        InvoiceId $invoiceId,
        int $statusId,
    ): bool;

    // /**
    //  * Ensure object has required properties with default values
    //  *
    //  * @param \stdClass $object Object to modify
    //  * @param array $properties Properties with default values
    //  * @return void
    //  */
    // public function ensureObjectProperties(\stdClass $object, array $properties): void;

    /**
     * Create an invoice for a user including on-the-fly client/supplier resolution and product persistence.
     *
     * @param  UserId  $userId  Owner user id
     * @param  array<string,mixed>  $data  Validated invoice attributes
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function createInvoice(
        UserId $userId,
        array $data,
        array $invoiceProducts,
    ): InvoiceDTO;

    /**
     * Update an invoice for a user including on-the-fly client/supplier resolution and product replacement.
     *
     * @param  UserId  $userId  Owner user id
     * @param  InvoiceId  $invoiceId  Invoice id
     * @param  array<string,mixed>  $data  Validated invoice attributes
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function updateInvoice(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        array $invoiceProducts,
    ): InvoiceDTO;

    /**
     * Delete an invoice for a user including related products.
     *
     * @param  UserId  $userId  Owner user id
     * @param  InvoiceId  $invoiceId  Invoice id
     */
    public function deleteInvoice(UserId $userId, InvoiceId $invoiceId): void;

    public function generateInvoiceNumber(UserId $userId, int $year): string;
}
