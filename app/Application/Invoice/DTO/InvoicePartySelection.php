<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

/**
 * What the user chose for the parties of an invoice: the business and its account are only selected, the
 * customer is entered (prefilled from the contact) and stored as entered.
 */
final readonly class InvoicePartySelection
{
    public function __construct(
        public BusinessId $businessId,
        public ?BankAccountId $bankAccountId,
        public ?ContactId $contactId,
        public DocumentPartyDTO $customer,
        public bool $saveCustomerToContacts,
        public bool $reloadBusiness,
    ) {}
}
