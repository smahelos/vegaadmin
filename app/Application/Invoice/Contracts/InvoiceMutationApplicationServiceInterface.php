<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Models\User;

interface InvoiceMutationApplicationServiceInterface
{
    /**
     * @param array<string,mixed> $data
     * @param list<InvoiceProductWriteData> $products
     */
    public function create(int $userId, array $data, array $products): InvoiceActionResult;
    /**
     * @param array<string,mixed> $data
     * @param list<InvoiceProductWriteData> $products
     */
    public function update(int $userId, int $invoiceId, array $data, array $products): InvoiceActionResult;
}
