<?php

namespace App\Domain\Invoice\Contracts;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contract for Invoice DTO read operations.
 * Keeps Domain layer independent from Eloquent by returning InvoiceDTO.
 */
interface InvoiceDtoReadRepositoryInterface
{
    /** Null when the invoice does not exist or is outside the scope. */
    public function findById(InvoiceId $id, ReadScope $scope): ?InvoiceDTO;

    /** Find invoice by ID for specific user (ownership enforced). */
    public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO;

    /** Locks and rereads the invoice in the surrounding transaction; null outside the scope. */
    public function findByIdForUpdate(InvoiceId $id, WriteScope $scope): ?InvoiceDTO;

    /** Highest invoice number of the owner and year in the standard format (see InvoiceNumber), null if none. */
    public function findLastInvoiceNumber(ReadScope $scope, int $year): ?string;
}
