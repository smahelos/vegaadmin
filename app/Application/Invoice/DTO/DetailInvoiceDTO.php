<?php

declare(strict_types=1);

namespace App\Application\Invoice\DTO;

use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Payment\Enums\PaymentMethodCode;

/**
 * The invoice detail page. The contact only tells whether the customer is linked to the address book;
 * without a link, the contact with the customer's Czech company number is offered instead of a duplicate, only
 * on a draft. A draft has the business's current VAT payer flag.
 */
final readonly class DetailInvoiceDTO
{
    public function __construct(
        public InvoiceDTO $invoice,
        public ?PaymentMethodCode $paymentMethod,
        public ?ContactDTO $contact,
        public ?DuplicatePartyDTO $contactToLink,
        public bool $vatPayer,
    ) {}
}
