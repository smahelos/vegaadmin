<?php

declare(strict_types=1);

namespace App\Domain\Party\DTO;

use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;

/**
 * Both parties and the payment account of an invoice or expense, with the address book records they were copied
 * from. A document without a contact has a one-off counterparty; the business is always set.
 */
final readonly class DocumentParties
{
    public function __construct(
        public BusinessId $businessId,
        public ?ContactId $contactId,
        public ?BankAccountId $bankAccountId,
        public DocumentPartyDTO $supplier,
        public DocumentPartyDTO $customer,
        public ?DocumentAccountDTO $account,
    ) {}
}
