<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * What issuing fixed on an invoice: its number, dates and the supplier's invoicing settings of that moment
 * (D-050, points 1 and 6).
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
    ) {}
}
