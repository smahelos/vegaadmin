<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Invoice\DTO\DetailInvoiceDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\DocumentParties;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\Enums\DuplicateReason;
use App\Domain\Party\Exceptions\BusinessNotFoundException;
use App\Domain\Party\Services\DuplicateFinder;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;

final class GetInvoiceHandler
{
    public function __construct(
        private readonly InvoiceDtoReadRepositoryInterface $invoices,
        private readonly ContactReadRepositoryInterface $contacts,
        private readonly PaymentMethodDtoReadRepositoryInterface $paymentMethods,
        private readonly DuplicateFinder $duplicates,
        private readonly BusinessReadRepositoryInterface $businesses,
    ) {}

    public function handle(GetInvoice $query, ReadScope $scope): DetailInvoiceDTO
    {
        $invoice = $this->invoices->findById($query->id, $scope)
            ?? throw new InvoiceNotFoundException('Invoice not accessible for user');
        $parties = $invoice->parties;
        $contact = null;
        $contactToLink = null;
        if ($parties->contactId === null && $invoice->issue === null) {
            $contactToLink = $this->contactToLink($parties, $scope);
        } elseif ($parties->contactId !== null) {
            $contact = $this->contacts->findById($parties->contactId, $scope);
        }
        $paymentMethod = null;
        if ($invoice->paymentMethodId !== null) {
            $paymentMethod = $this->paymentMethods->findById($invoice->paymentMethodId);
        }

        $issue = $invoice->issue;
        if ($issue !== null) {
            return new DetailInvoiceDTO(
                $invoice,
                $paymentMethod?->code,
                $contact,
                null,
                $issue->vatPayer,
            );
        }
        // A draft follows the business until it is issued (D-050, point 6)
        $business = $this->businesses->findById($parties->businessId, $scope)
            ?? throw new BusinessNotFoundException($parties->businessId);

        return new DetailInvoiceDTO(
            $invoice,
            $paymentMethod?->code,
            $contact,
            $contactToLink,
            $business->invoicing->vatPayer,
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
