<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoicePartySelection;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;

interface InvoiceMutationApplicationServiceInterface
{
    /**
     * @param  array<string,mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function create(
        int $userId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceActionResult;

    /**
     * @param  array<string,mixed>  $data
     * @param  list<InvoiceProductWriteData>  $products
     */
    public function update(
        int $userId,
        int $invoiceId,
        array $data,
        InvoicePartySelection $parties,
        array $products,
    ): InvoiceActionResult;
}
