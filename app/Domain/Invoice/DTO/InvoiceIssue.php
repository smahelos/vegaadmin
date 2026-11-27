<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Invoice\ValueObjects\StoredPdf;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * What issuing fixed on an invoice: its number, dates, the supplier's invoicing settings of that moment and the PDF
 * as delivered (D-050, points 1, 5, 6 and 8). The PDF is null only inside the issuing transaction, before its file
 * is written.
 */
final readonly class InvoiceIssue
{
    public function __construct(
        public InvoiceNumber $number,
        public DateTimeImmutable $date,
        public DateTimeImmutable $taxPointDate,
        public DateTimeImmutable $issuedAt,
        public UserId $issuedBy,
        public bool $vatPayer,
        public ?string $registerEntry,
        public ?StoredPdf $pdf = null,
    ) {}
}
