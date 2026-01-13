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
    /** Find invoice by ID (admin access - no user scoping). */
    public function findByIdAny(InvoiceId $id): ?InvoiceDTO;

    /** Find invoice by ID for specific user (ownership enforced). */
    public function findByIdForUser(InvoiceId $id, UserId $userId): ?InvoiceDTO;

    /** Find unpaid invoices for a user. */
    public function findUnpaidForUser(UserId $userId): array;

    /** Find overdue invoices for a user. */
    public function findOverdueForUser(UserId $userId): array;

    /** Get invoice count for a user. */
    public function getUserInvoiceCount(UserId $userId): int;

    /** Find last invoice number for a user and year. */
    public function findLastInvoiceNumber(UserId $userId, int $year): ?string;

    /** Check if invoice exists with given number for a user. */
    public function existsWithNumber(UserId $userId, string $number): bool;
}
