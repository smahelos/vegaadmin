<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\DTO\DocumentParties;
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
     * @return bool Success status
     */
    public function setInvoiceTemplate(InvoiceId $id, string $template): bool;

    // /**
    //  * Ensure object has required properties with default values
    //  *
    //  * @param \stdClass $object Object to modify
    //  * @param array $properties Properties with default values
    //  * @return void
    //  */
    // public function ensureObjectProperties(\stdClass $object, array $properties): void;

    /**
     * Create an invoice for a user with its parties and lines.
     *
     * @param  UserId  $userId  Owner user id
     * @param  array<string,mixed>  $data  Validated invoice attributes
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function createInvoice(
        UserId $userId,
        array $data,
        DocumentParties $parties,
        array $invoiceProducts,
    ): InvoiceDTO;

    /**
     * Update an invoice for a user with its parties, replacing its lines.
     *
     * @param  UserId  $userId  Owner user id
     * @param  array<string,mixed>  $data  Validated invoice attributes
     * @param  list<InvoiceProductWriteData>  $invoiceProducts
     */
    public function updateInvoice(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        DocumentParties $parties,
        ?array $invoiceProducts,
    ): InvoiceDTO;

    public function generateInvoiceNumber(UserId $userId, int $year): string;
}
