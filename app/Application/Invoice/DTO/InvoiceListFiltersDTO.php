<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoicePartyFilterDTO;

final readonly class InvoiceListFiltersDTO
{
    /**
     * @param  list<InvoicePartyFilterDTO>  $businesses
     * @param  list<InvoicePartyFilterDTO>  $contacts
     */
    public function __construct(
        public array $businesses,
        public array $contacts,
    ) {}
}
