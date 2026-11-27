<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use DateTimeImmutable;

/**
 * What the issue dialog of a draft is prefilled with (D-050, point 10).
 */
final readonly class IssueInvoiceFormDTO
{
    public function __construct(
        public InvoiceNumber $number,
        public DateTimeImmutable $issueDate,
    ) {}
}
