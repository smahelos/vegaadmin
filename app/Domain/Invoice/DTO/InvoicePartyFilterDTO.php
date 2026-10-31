<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

final readonly class InvoicePartyFilterDTO
{
    public function __construct(
        public BusinessId|ContactId $id,
        public string $name,
        public bool $archived,
    ) {}
}
