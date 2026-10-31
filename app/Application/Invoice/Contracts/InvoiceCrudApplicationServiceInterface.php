<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for invoice CRUD operations
 */
interface InvoiceCrudApplicationServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        UserId $userId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceDTO;

    /**
     * @param  array<string, mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        UserId $userId,
        InvoiceId $invoiceId,
        array $data,
        InvoicePartySelection $parties,
        ?array $products,
    ): InvoiceDTO;
}
