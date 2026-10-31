<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

final readonly class InvoiceSearch
{
    public function __construct(
        public string $term,
        public ?InvoiceStatus $status,
        public ?BusinessId $businessId,
        public ?ContactId $contactId,
        public string $sort,
        public bool $ascending,
    ) {}
}
