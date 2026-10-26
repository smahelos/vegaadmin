<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for Invoice DTO read operations.
 * Keeps Domain layer independent from Eloquent by returning InvoiceDTO.
 */
interface InvoiceDtoReadRepositoryInterface
{
    /** Find invoice by ID for specific user (ownership enforced). */
    public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO;

    /** Highest invoice number of a user and year in the standard format (see InvoiceNumber), null if none. */
    public function findLastInvoiceNumber(UserId $userId, int $year): ?string;
}
