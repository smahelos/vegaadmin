<?php

namespace App\Application\Invoice\Contracts;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Models\User;

interface InvoiceMutationApplicationServiceInterface
{
    /** @param array<string,mixed> $data @param array<int,mixed> $products */
    public function create(int $userId, array $data, array $products): InvoiceActionResult;
    /** @param array<string,mixed> $data @param array<int,mixed> $products */
    public function update(int $userId, int $invoiceId, array $data, array $products): InvoiceActionResult;
}
