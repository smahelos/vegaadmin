<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\DTO\InvoiceWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceId;

/**
 * Contract for Invoice DTO write operations.
 */
interface InvoiceDtoWriteRepositoryInterface
{
    public function create(InvoiceWriteData $writeData): InvoiceDTO;

    public function update(InvoiceId $id, InvoiceWriteData $writeData): InvoiceDTO;

    public function delete(InvoiceId $id): void;

    public function markAsPaid(InvoiceId $id, int $paidStatusId): bool;

    public function setTemplate(InvoiceId $id, string $template): bool;
    
    public function changeStatus(InvoiceId $id, int $statusId): bool;
}
