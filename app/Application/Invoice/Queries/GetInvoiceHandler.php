<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

/**
 * The invoice detail: the stored parties, and the contact only tells whether the customer is linked to the
 * address book.
 */
final class GetInvoiceHandler
{
    public function __construct(
        private readonly InvoiceReadRepositoryInterface $invoiceModels,
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly InvoiceProductReadRepositoryInterface $products,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly DuplicateFinder $duplicates,
    ) {}

    public function handle(GetInvoice $query, ReadScope $scope): InvoiceFormDTO
    {
        $invoice = $this->invoiceModels->find($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $stored = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $parties = $stored->parties;
        $contact = null;
        $contactToLink = null;
        if ($parties->contactId === null) {
            $contactToLink = $this->contactToLink($parties, $scope);
        } else {
            $contact = $this->contacts->findById($parties->contactId, $scope);
        }

        return new InvoiceFormDTO(
            new FieldSetDTO([]),
            [
                'invoice' => $invoice,
                'paymentAmount' => $invoice->payment_amount_money,
                'subtotal' => $invoice->subtotal_money,
                'totalTax' => $invoice->total_tax_money,
                'parties' => $parties,
                'contact' => $contact,
                'contactToLink' => $contactToLink,
                'invoiceProducts' => $this->products->allForInvoice($query->id->value),
            ],
        );
    }

    /** The contact with the customer's Czech company number, offered to link instead of creating a duplicate. */
    private function contactToLink(DocumentParties $parties, ReadScope $scope): ?DuplicatePartyDTO
    {
        $profile = $parties->customer->toProfile();
        $candidates = $this->contacts->listPossibleDuplicates(
            $scope,
            $profile,
            null,
        );
        foreach ($this->duplicates->find($profile, $candidates) as $duplicate) {
            if ($duplicate->reason === DuplicateReason::CZECH_COMPANY_NUMBER) {
                return $duplicate;
            }
        }

        return null;
    }
}
